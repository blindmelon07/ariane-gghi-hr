<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\SyncLog;
use App\Services\ActivityLogService;
use App\Services\ZKTecoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEmployeesCommand extends Command
{
    protected $signature = 'sync:employees';
    protected $description = 'Sync employees from the ZKTeco device';

    public function handle(ZKTecoService $zk): int
    {
        $this->info('Syncing employees from ZKTeco device...');

        $syncLog = SyncLog::create([
            'type'       => 'employees',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $zk->syncUsers();
            $count  = $result['synced'];

            // If SYNC_API_URL is configured, push employees to the online app
            $apiUrl = rtrim(env('SYNC_API_URL', ''), '/');
            if ($apiUrl) {
                $this->pushToOnlineApp($apiUrl);
            }

            $syncLog->update([
                'status'          => 'success',
                'records_fetched' => $count,
                'completed_at'    => now(),
            ]);

            ActivityLogService::log('employees_synced', "Synced {$count} employees from ZKTeco device.");
            $this->info("Done. Synced {$count} of {$result['total']} employees.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $syncLog->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function pushToOnlineApp(string $apiUrl): void
    {
        $employees = Employee::where('is_active', true)
            ->whereNotNull('synced_at')
            ->get()
            ->map(fn ($emp) => [
                'emp_code'   => $emp->emp_code,
                'first_name' => $emp->first_name,
                'last_name'  => $emp->last_name,
            ])
            ->values()
            ->all();

        if (empty($employees)) {
            return;
        }

        $response = Http::withToken(env('SYNC_API_TOKEN', ''))
            ->timeout(30)
            ->post("{$apiUrl}/api/sync/employees", ['employees' => $employees]);

        if ($response->successful()) {
            $this->info("Pushed {$response->json('synced')} employees to online app.");
        } else {
            Log::warning('SyncEmployeesCommand: Failed to push to online app — ' . $response->body());
            $this->warn('Could not push to online app: ' . $response->status());
        }
    }
}
