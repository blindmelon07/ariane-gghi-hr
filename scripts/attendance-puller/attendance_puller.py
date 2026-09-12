#!/usr/bin/env python3
"""
GGHI HR — Desktop Attendance Puller
=====================================

Runs on a Windows/Mac/Linux desktop that has network access to the ZKTeco
biometric device (same office LAN), pulls punch logs (and optionally the
device's user list), and pushes them to the GGHI HR Portal over HTTPS/HTTP
via the existing /api/sync/attendance and /api/sync/employees endpoints.

This exists because the Laravel app itself may be hosted somewhere that
can't reach the device directly (e.g. a public server, or simply a
different machine than the one on the office LAN) — this script is the
"local machine" the Biometrics admin page refers to when it says
"ZKTeco sync is managed by the local machine."

Requirements
------------
    pip install -r requirements.txt

Configuration
-------------
Copy config.example.ini to config.ini (kept out of git — it holds a secret
token) and fill in your values. See config.example.ini for what each field
means.

Usage
-----
    python attendance_puller.py                  # one-shot: pull + push, then exit
    python attendance_puller.py --sync-users      # also sync the device's user list
    python attendance_puller.py --daemon          # loop forever, syncing every --interval minutes
    python attendance_puller.py --daemon --interval 15
    python attendance_puller.py --from 2026-09-01 --to 2026-09-12   # only push punches in this range
    python attendance_puller.py --dry-run         # pull from the device, print what WOULD be
                                                    # pushed, but don't actually push or clear anything

Scheduling on Windows (instead of --daemon)
--------------------------------------------
Task Scheduler → Create Task → Trigger: "Repeat task every 15 minutes,
for a duration of Indefinitely" → Action: "Start a program"
    Program:  C:\\path\\to\\python.exe
    Arguments: attendance_puller.py --sync-users
    Start in:  C:\\path\\to\\this\\folder

This script never deletes or clears attendance from the device — it only
reads. Re-running it is always safe: the server's sync endpoint upserts by
(emp_code, punch_time), so duplicate pushes are harmless no-ops.
"""

from __future__ import annotations

import argparse
import configparser
import logging
import sys
import time
from datetime import datetime, timedelta
from logging.handlers import RotatingFileHandler
from pathlib import Path

import warnings
# requests' optional charset-detection dependency (chardet/charset_normalizer)
# doesn't always survive being frozen into a standalone .exe by PyInstaller.
# It's only used to guess text encoding on ambiguous responses — irrelevant
# here since the server always returns UTF-8 JSON — so silence the warning
# rather than fight PyInstaller over an unused optional dependency.
warnings.filterwarnings("ignore", message="Unable to find acceptable character detection dependency")

try:
    import requests
except ImportError:
    print("Missing dependency 'requests'. Run: pip install -r requirements.txt", file=sys.stderr)
    sys.exit(1)

try:
    from zk import ZK
    from zk.exception import ZKErrorConnection, ZKErrorResponse, ZKNetworkError
except ImportError:
    print("Missing dependency 'pyzk'. Run: pip install -r requirements.txt", file=sys.stderr)
    sys.exit(1)

# When frozen into a .exe by PyInstaller, __file__ resolves to a temporary
# extraction folder (e.g. _MEIxxxxxx) that's wiped after the process exits —
# config.ini and the log file need to live next to the actual .exe instead.
if getattr(sys, "frozen", False):
    BASE_DIR = Path(sys.executable).resolve().parent
else:
    BASE_DIR = Path(__file__).resolve().parent
CONFIG_PATH = BASE_DIR / "config.ini"
LOG_PATH = BASE_DIR / "attendance_puller.log"

# Batch size for pushing punches — keeps each HTTP request body reasonably
# sized even if the device is holding weeks of unsynced logs.
PUSH_BATCH_SIZE = 500

# How many times to retry a single HTTP push before giving up on that batch.
HTTP_RETRIES = 3
HTTP_RETRY_DELAY_SECONDS = 5


# ── Logging ──────────────────────────────────────────────────────────────

def setup_logging(verbose: bool) -> logging.Logger:
    logger = logging.getLogger("attendance_puller")
    logger.setLevel(logging.DEBUG if verbose else logging.INFO)

    fmt = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s", "%Y-%m-%d %H:%M:%S")

    console = logging.StreamHandler(sys.stdout)
    console.setFormatter(fmt)
    logger.addHandler(console)

    # Rotate at 2MB, keep 5 backups — plenty for a script that runs every 15 min.
    file_handler = RotatingFileHandler(LOG_PATH, maxBytes=2 * 1024 * 1024, backupCount=5, encoding="utf-8")
    file_handler.setFormatter(fmt)
    logger.addHandler(file_handler)

    return logger


# ── Config ───────────────────────────────────────────────────────────────

class Config:
    def __init__(self, path: Path):
        if not path.exists():
            raise SystemExit(
                f"Config file not found: {path}\n"
                f"Copy config.example.ini to config.ini and fill in your values first."
            )

        parser = configparser.ConfigParser()
        parser.read(path, encoding="utf-8")

        try:
            device = parser["device"]
            server = parser["server"]
        except KeyError as e:
            raise SystemExit(f"config.ini is missing a required section: {e}")

        self.device_ip: str = device.get("ip", "").strip()
        self.device_port: int = device.getint("port", fallback=4370)
        self.device_timeout: int = device.getint("timeout_seconds", fallback=10)
        self.device_password: int = device.getint("password", fallback=0)
        self.device_force_udp: bool = device.getboolean("force_udp", fallback=False)

        self.server_base_url: str = server.get("base_url", "").strip().rstrip("/")
        self.api_token: str = server.get("api_token", "").strip()
        self.verify_ssl: bool = server.getboolean("verify_ssl", fallback=True)

        if not self.device_ip:
            raise SystemExit("config.ini: [device] ip is required.")
        if not self.server_base_url:
            raise SystemExit("config.ini: [server] base_url is required.")
        if not self.api_token:
            raise SystemExit("config.ini: [server] api_token is required.")


# ── ZKTeco device ────────────────────────────────────────────────────────

def connect_device(cfg: Config, logger: logging.Logger) -> ZK:
    zk = ZK(
        cfg.device_ip,
        port=cfg.device_port,
        timeout=cfg.device_timeout,
        password=cfg.device_password,
        force_udp=cfg.device_force_udp,
        ommit_ping=False,
    )

    logger.info(f"Connecting to device at {cfg.device_ip}:{cfg.device_port} ...")
    conn = zk.connect()
    logger.info(f"Connected. Serial: {conn.get_serialnumber()}, firmware: {conn.get_firmware_version()}")
    return conn


def pull_attendance(conn: ZK, logger: logging.Logger, date_from: datetime | None, date_to: datetime | None) -> list[dict]:
    """
    Pulls all attendance records currently stored on the device and returns
    them as plain dicts ready for the API, optionally filtered to a date
    range. The device itself is only put into "disabled" mode (fingerprint
    reader paused) for the few seconds it takes to read the log — nothing is
    erased from it.
    """
    conn.disable_device()
    try:
        records = conn.get_attendance() or []
    finally:
        conn.enable_device()

    logger.info(f"Device reports {len(records)} attendance record(s) in its log.")

    payload = []
    skipped = 0

    for rec in records:
        ts = rec.timestamp
        if date_from and ts < date_from:
            skipped += 1
            continue
        if date_to and ts > date_to:
            skipped += 1
            continue

        emp_code = str(rec.user_id).strip()
        if not emp_code:
            skipped += 1
            continue

        payload.append({
            "emp_code": emp_code,
            "punch_time": ts.strftime("%Y-%m-%d %H:%M:%S"),
            # pyzk's Attendance.punch is the check type (0=Check In, 1=Check
            # Out, etc. — device-dependent) and .status is the verify method
            # (fingerprint/password/card). This mirrors the field mapping the
            # app's own PHP ZKTecoService uses for the same device protocol.
            "punch_state": int(rec.punch),
            "verify_type": int(rec.status),
        })

    if skipped:
        logger.info(f"Skipped {skipped} record(s) outside the requested date range or missing an ID.")

    return payload


def pull_users(conn: ZK, logger: logging.Logger) -> list[dict]:
    users = conn.get_users() or []
    logger.info(f"Device reports {len(users)} enrolled user(s).")

    payload = []
    for u in users:
        emp_code = str(u.user_id).strip()
        if not emp_code:
            continue

        name = (u.name or "").strip()
        parts = name.split(" ", 1)
        first_name = parts[0] if parts else name
        last_name = parts[1] if len(parts) > 1 else ""

        payload.append({
            "emp_code": emp_code,
            "first_name": first_name,
            "last_name": last_name,
        })

    return payload


# ── Server push ──────────────────────────────────────────────────────────

def chunked(items: list, size: int):
    for i in range(0, len(items), size):
        yield items[i:i + size]


def push_batch(cfg: Config, logger: logging.Logger, endpoint: str, body_key: str, items: list[dict]) -> int:
    """Pushes one batch with retries. Returns the number of records the server confirmed."""
    url = f"{cfg.server_base_url}{endpoint}"
    headers = {"Authorization": f"Bearer {cfg.api_token}", "Accept": "application/json"}

    last_error: Exception | None = None

    for attempt in range(1, HTTP_RETRIES + 1):
        try:
            resp = requests.post(url, json={body_key: items}, headers=headers, verify=cfg.verify_ssl, timeout=30)

            if resp.status_code == 401:
                raise SystemExit(
                    "Server rejected the request as Unauthorized (401). "
                    "Check that config.ini's api_token matches the server's SYNC_API_TOKEN."
                )

            resp.raise_for_status()
            data = resp.json()
            synced = data.get("synced", len(items))
            logger.info(f"  → batch of {len(items)} pushed, server confirmed {synced} synced.")
            return synced

        except requests.RequestException as e:
            last_error = e
            logger.warning(f"  Push attempt {attempt}/{HTTP_RETRIES} failed: {e}")
            if attempt < HTTP_RETRIES:
                time.sleep(HTTP_RETRY_DELAY_SECONDS)

    logger.error(f"  Giving up on this batch after {HTTP_RETRIES} attempts: {last_error}")
    return 0


def push_attendance(cfg: Config, logger: logging.Logger, records: list[dict]) -> int:
    if not records:
        logger.info("No attendance records to push.")
        return 0

    total_synced = 0
    batches = list(chunked(records, PUSH_BATCH_SIZE))
    logger.info(f"Pushing {len(records)} record(s) to server in {len(batches)} batch(es)...")

    for batch in batches:
        total_synced += push_batch(cfg, logger, "/api/sync/attendance", "records", batch)

    return total_synced


def push_users(cfg: Config, logger: logging.Logger, users: list[dict]) -> int:
    if not users:
        logger.info("No users to push.")
        return 0

    total_synced = 0
    batches = list(chunked(users, PUSH_BATCH_SIZE))
    logger.info(f"Pushing {len(users)} user(s) to server in {len(batches)} batch(es)...")

    for batch in batches:
        total_synced += push_batch(cfg, logger, "/api/sync/employees", "employees", batch)

    return total_synced


# ── Orchestration ────────────────────────────────────────────────────────

def run_once(cfg: Config, logger: logging.Logger, args: argparse.Namespace) -> bool:
    """Returns True on success, False if anything went wrong."""
    date_from = datetime.strptime(args.date_from, "%Y-%m-%d") if args.date_from else None
    date_to = (
        datetime.strptime(args.date_to, "%Y-%m-%d") + timedelta(days=1) - timedelta(seconds=1)
        if args.date_to else None
    )

    conn = None
    try:
        conn = connect_device(cfg, logger)

        attendance_records = pull_attendance(conn, logger, date_from, date_to)
        user_records = pull_users(conn, logger) if args.sync_users else []

    except (ZKErrorConnection, ZKNetworkError, ZKErrorResponse) as e:
        logger.error(f"Could not communicate with the ZKTeco device: {e}")
        return False
    except Exception as e:
        logger.exception(f"Unexpected error while reading from the device: {e}")
        return False
    finally:
        if conn is not None:
            try:
                conn.disconnect()
            except Exception:
                pass

    if args.dry_run:
        logger.info(f"[DRY RUN] Would push {len(attendance_records)} attendance record(s) and {len(user_records)} user(s). Nothing sent.")
        return True

    ok = True
    try:
        push_attendance(cfg, logger, attendance_records)
        if args.sync_users:
            push_users(cfg, logger, user_records)
    except SystemExit:
        raise
    except Exception as e:
        logger.exception(f"Unexpected error while pushing to server: {e}")
        ok = False

    return ok


def main() -> int:
    parser = argparse.ArgumentParser(description="Pull attendance from a ZKTeco device and push it to the GGHI HR Portal.")
    parser.add_argument("--config", default=str(CONFIG_PATH), help="Path to config.ini (default: alongside this script)")
    parser.add_argument("--sync-users", action="store_true", help="Also sync the device's enrolled user list")
    parser.add_argument("--from", dest="date_from", default=None, metavar="YYYY-MM-DD", help="Only push punches on/after this date")
    parser.add_argument("--to", dest="date_to", default=None, metavar="YYYY-MM-DD", help="Only push punches on/before this date")
    parser.add_argument("--dry-run", action="store_true", help="Read from the device but don't push or modify anything")
    parser.add_argument("--daemon", action="store_true", help="Run forever, syncing every --interval minutes")
    parser.add_argument("--interval", type=int, default=15, metavar="MINUTES", help="Interval for --daemon mode (default: 15)")
    parser.add_argument("-v", "--verbose", action="store_true", help="Verbose (debug) logging")
    args = parser.parse_args()

    logger = setup_logging(args.verbose)
    cfg = Config(Path(args.config))

    if not args.daemon:
        ok = run_once(cfg, logger, args)
        return 0 if ok else 1

    logger.info(f"Starting in daemon mode — syncing every {args.interval} minute(s). Press Ctrl+C to stop.")
    try:
        while True:
            run_once(cfg, logger, args)
            logger.info(f"Sleeping {args.interval} minute(s) until next sync...")
            time.sleep(args.interval * 60)
    except KeyboardInterrupt:
        logger.info("Stopped by user.")
        return 0


if __name__ == "__main__":
    sys.exit(main())
