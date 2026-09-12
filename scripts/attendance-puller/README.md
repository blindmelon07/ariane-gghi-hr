# Attendance Puller (Desktop)

A standalone Python script that runs on a desktop with network access to the
ZKTeco biometric device (same office LAN) and pushes punch logs to the GGHI
HR Portal. This is the "local machine" the admin Biometrics page refers to
when it says *"ZKTeco sync is managed by the local machine. Attendance data
is pushed here automatically every 15 minutes."*

It only **reads** from the device — it never clears or deletes anything on
it, so it's safe to run as often as you like.

## Option A — Just run the .exe (no Python needed)

If you don't want to install Python on the desktop that runs this, build a
standalone Windows executable once from a machine that *does* have Python:

```
cd scripts/attendance-puller
build.bat
```

This produces `dist\AttendancePuller.exe` — a single file with Python and
all dependencies bundled in (~11MB). Copy that .exe plus a `config.ini`
(see step 2 below — same config file either way) to any Windows desktop and
run it directly, e.g.:

```
AttendancePuller.exe --dry-run -v
AttendancePuller.exe --sync-users
```

`config.ini` must sit in the **same folder** as the .exe. Everything else
below (scheduling, troubleshooting, field mapping) applies identically —
just swap `python attendance_puller.py` for `AttendancePuller.exe`.

## Option B — Run with Python installed

## 1. Install

```
cd scripts/attendance-puller
pip install -r requirements.txt
```

Requires Python 3.9+.

## 2. Configure

```
copy config.example.ini config.ini
```

Edit `config.ini`:

| Field | Where to get it |
|---|---|
| `device.ip` / `device.port` | Your ZKTeco device's network settings (same as `ZK_IP` / `ZK_PORT` in the app's `.env`) |
| `server.base_url` | The HR Portal's URL, e.g. `https://hr.yourcompany.com` |
| `server.api_token` | Must match `SYNC_API_TOKEN` in the server's `.env` file — ask whoever manages the server, or generate a new one and update both places |

`config.ini` is gitignored — it holds a secret token, so don't commit it or
share it outside your organization.

## 3. Run it

**One-time test run** (recommended first — doesn't push anything):

```
python attendance_puller.py --dry-run -v
```

This connects to the device, reads the log, and prints what it *would*
push. Check the output for errors before doing a real run.

**Real run**, including syncing the device's employee list:

```
python attendance_puller.py --sync-users
```

**Only a specific date range** (e.g. re-pushing last week after a server
outage):

```
python attendance_puller.py --from 2026-09-01 --to 2026-09-07
```

**Run continuously**, syncing every 15 minutes (Ctrl+C to stop):

```
python attendance_puller.py --daemon --interval 15 --sync-users
```

## 4. Schedule it (recommended over `--daemon`)

`--daemon` mode works, but it dies if the desktop restarts or the terminal
closes. For real use, schedule it instead:

### Windows (Task Scheduler)

1. Open **Task Scheduler** → **Create Task…**
2. **General** tab: name it "GGHI Attendance Puller", check "Run whether
   user is logged on or not".
3. **Triggers** tab → New → "Daily", check "Repeat task every: 15 minutes",
   "for a duration of: Indefinitely".
4. **Actions** tab → New → "Start a program":
   - **If using the .exe**: Program/script: full path to `AttendancePuller.exe`; Add arguments: `--sync-users`
   - **If using Python directly**: Program/script: full path to `python.exe` (find it with `where python`); Add arguments: `attendance_puller.py --sync-users`
   - Start in: the folder containing the .exe (or the script + config.ini)
5. Save. Right-click the task → **Run** once to confirm it works, then
   check `attendance_puller.log` in this folder.

### macOS / Linux (cron)

```
*/15 * * * * cd /path/to/scripts/attendance-puller && /usr/bin/python3 attendance_puller.py --sync-users >> cron.log 2>&1
```

## Troubleshooting

- **`Could not communicate with the ZKTeco device`** — check the device is
  powered on, on the same network as this machine, and that `device.ip` /
  `device.port` in `config.ini` are correct. Try pinging the device's IP.
- **`Server rejected the request as Unauthorized (401)`** — `api_token` in
  `config.ini` doesn't match `SYNC_API_TOKEN` on the server. Fix one to
  match the other.
- **Nothing seems to sync but no errors** — run with `--dry-run -v` to see
  exactly what the device returned and whether any records were filtered
  out by `--from`/`--to`.
- Logs are written to `attendance_puller.log` in this folder (rotates at
  2MB, keeps 5 backups) as well as the console.

## How the data maps

Each device attendance record becomes one row pushed to
`POST /api/sync/attendance`:

| Device field (pyzk) | Sent as | Notes |
|---|---|---|
| `user_id` | `emp_code` | Must match an employee's `emp_code` in the HR system to be linked; unmatched codes are still stored, just unlinked. |
| `timestamp` | `punch_time` | `YYYY-MM-DD HH:MM:SS` |
| `punch` | `punch_state` | Check type (in/out) — meaning is device/firmware-dependent |
| `status` | `verify_type` | Verification method (fingerprint/card/password) |

If punch types come through looking swapped or wrong for your specific
device model, run `--dry-run -v` and compare the raw values against a known
punch on the device's own display — the `punch`/`status` field meanings can
vary by ZKTeco firmware, and the mapping above may need adjusting in
`attendance_puller.py`'s `pull_attendance()` function.
