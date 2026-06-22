<?php

namespace App\Jobs;

use App\Models\SyncLog;
use App\Services\ActivityLogService;
use App\Services\ZKTecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('biotime');
    }

    public function handle(ZKTecoService $zk): void
    {
        $syncLog = SyncLog::create([
            'type'       => 'employees',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $zk->syncUsers();
            $count  = $result['synced'];

            $syncLog->update([
                'status'          => 'success',
                'records_fetched' => $count,
                'completed_at'    => now(),
            ]);

            Log::info("SyncEmployeesJob: Successfully synced {$count} employees from device.");
            ActivityLogService::log('employees_synced', "Synced {$count} employees from ZKTeco device.");
        } catch (\Throwable $e) {
            $syncLog->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            Log::error('SyncEmployeesJob: Failed -- ' . $e->getMessage());
            throw $e;
        }
    }
}
