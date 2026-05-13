<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ZKTecoService
{
    protected string $ip;
    protected int $port;

    public function __construct()
    {
        $this->ip   = config('zkteco.ip', '');
        $this->port = (int) config('zkteco.port', 4370);
    }

    protected function device(): ZKTeco
    {
        return new ZKTeco($this->ip, $this->port);
    }

    public function testConnection(): array
    {
        if (empty($this->ip)) {
            return ['connected' => false, 'message' => 'ZK_IP is not configured.'];
        }

        $zk = $this->device();

        try {
            $connected = $zk->connect();

            if (!$connected) {
                return ['connected' => false, 'message' => 'Device did not respond. Check IP/port and network.'];
            }

            $serial = $zk->serialNumber();
            $zk->disconnect();

            return [
                'connected' => true,
                'message'   => 'Connected successfully.',
                'serial'    => $serial,
                'ip'        => $this->ip,
                'port'      => $this->port,
            ];
        } catch (\Throwable $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Pull ALL attendance logs from the device and upsert into attendance_logs.
     * Returns count of records synced.
     */
    public function syncAttendance(?string $fromDate = null, ?string $toDate = null): array
    {
        $zk = $this->device();

        if (!$zk->connect()) {
            throw new \RuntimeException('Could not connect to ZKTeco device at ' . $this->ip);
        }

        try {
            $zk->disableDevice();
            $records = $zk->getAttendance();
            $zk->enableDevice();
            $zk->disconnect();
        } catch (\Throwable $e) {
            try { $zk->enableDevice(); $zk->disconnect(); } catch (\Throwable) {}
            throw $e;
        }

        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : null;
        $to   = $toDate   ? Carbon::parse($toDate)->endOfDay()     : null;

        $count   = 0;
        $skipped = 0;

        foreach ($records as $record) {
            $empCode   = trim($record['id'] ?? '');
            $timestamp = $record['timestamp'] ?? null;

            if (!$empCode || !$timestamp) {
                $skipped++;
                continue;
            }

            $punchCarbon = Carbon::parse($timestamp);

            if ($from && $punchCarbon->lt($from)) { $skipped++; continue; }
            if ($to   && $punchCarbon->gt($to))   { $skipped++; continue; }

            $employee = Employee::where('emp_code', $empCode)->first();

            AttendanceLog::updateOrCreate(
                ['emp_code' => $empCode, 'punch_time' => $punchCarbon],
                [
                    'employee_id'  => $employee?->id,
                    'punch_date'   => $punchCarbon->toDateString(),
                    'punch_state'  => $record['state'] ?? 0,
                    'verify_type'  => $record['type']  ?? 0,
                    'terminal_sn'  => null,
                    'is_processed' => false,
                ]
            );

            $count++;
        }

        Log::info("ZKTecoService: synced {$count} records (skipped {$skipped}).");

        return ['synced' => $count, 'skipped' => $skipped, 'total' => count($records)];
    }

    /**
     * Pull users from the device and upsert into employees.
     * Returns count of records synced.
     */
    public function syncUsers(): array
    {
        $zk = $this->device();

        if (!$zk->connect()) {
            throw new \RuntimeException('Could not connect to ZKTeco device at ' . $this->ip);
        }

        try {
            $zk->disableDevice();
            $users = $zk->getUser();
            $zk->enableDevice();
            $zk->disconnect();
        } catch (\Throwable $e) {
            try { $zk->enableDevice(); $zk->disconnect(); } catch (\Throwable) {}
            throw $e;
        }

        $count = 0;

        foreach ($users as $user) {
            $empCode = trim($user['userid'] ?? '');
            $name    = trim($user['name'] ?? '');

            if (!$empCode) continue;

            $parts     = explode(' ', $name, 2);
            $firstName = $parts[0] ?? $name;
            $lastName  = $parts[1] ?? '';

            Employee::updateOrCreate(
                ['emp_code' => $empCode],
                [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'is_active'  => true,
                    'synced_at'  => now(),
                ]
            );

            $count++;
        }

        Log::info("ZKTecoService: synced {$count} users from device.");

        return ['synced' => $count, 'total' => count($users)];
    }
}
