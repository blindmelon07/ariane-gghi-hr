<?php

namespace App\Console\Commands;

use App\Models\SyncLog;
use App\Services\ZKTecoService;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncAttendanceCommand extends Command
{
    protected $signature = 'sync:attendance {--from= : Start date (Y-m-d)} {--to= : End date (Y-m-d)}';
    protected $description = 'Sync attendance logs from the ZKTeco device';

    public function handle(ZKTecoService $zk): int
    {
        $from = $this->option('from') ?? now()->toDateString();
        $to   = $this->option('to')   ?? $from;

        $this->info("Syncing attendance from {$from} to {$to}...");

        $syncLog = SyncLog::create([
            'type'       => 'attendance',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $result  = $zk->syncAttendance($from, $to);
            $synced  = $result['synced'];
        } catch (\Throwable $e) {
            $syncLog->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Device sync succeeded. Pushing to the online app is best-effort —
        // a failure here shouldn't mark the (successful) device sync as failed.
        $pushError = null;
        $apiUrl    = rtrim(env('SYNC_API_URL', ''), '/');
        if ($apiUrl) {
            try {
                $this->pushToOnlineApp($apiUrl, $from, $to);
            } catch (\Throwable $e) {
                $pushError = $e->getMessage();
                Log::warning('SyncAttendanceCommand: Failed to push to online app — ' . $pushError);
                $this->warn('Could not push to online app: ' . $pushError);
            }
        }

        $syncLog->update([
            'status'          => $pushError ? 'partial' : 'success',
            'records_fetched' => $synced,
            'error_message'   => $pushError,
            'completed_at'    => now(),
        ]);

        $this->info("Done. Synced: {$synced}, Skipped: {$result['skipped']}, Total on device: {$result['total']}");
        return self::SUCCESS;
    }

    private function pushToOnlineApp(string $apiUrl, string $from, string $to): void
    {
        $records = AttendanceLog::whereBetween('punch_date', [$from, $to])
            ->get()
            ->map(fn ($log) => [
                'emp_code'    => $log->emp_code,
                'punch_time'  => $log->punch_time,
                'punch_state' => $log->punch_state,
                'verify_type' => $log->verify_type,
                'terminal_sn' => $log->terminal_sn,
            ])
            ->values()
            ->all();

        if (empty($records)) {
            return;
        }

        $response = Http::withToken(env('SYNC_API_TOKEN', ''))
            ->timeout(30)
            ->post("{$apiUrl}/api/sync/attendance", ['records' => $records]);

        if ($response->successful()) {
            $this->info("Pushed {$response->json('synced')} records to online app.");
        } else {
            Log::warning('SyncAttendanceCommand: Failed to push to online app — ' . $response->body());
            $this->warn('Could not push to online app: ' . $response->status());
        }
    }
}
