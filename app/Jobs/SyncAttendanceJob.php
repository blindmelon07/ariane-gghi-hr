<?php

namespace App\Jobs;

use App\Models\SyncLog;
use App\Services\ZKTecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $fromDate = null,
        public ?string $toDate   = null,
    ) {
        $this->onQueue('biotime');
    }

    public function handle(ZKTecoService $zk): void
    {
        $from = $this->fromDate ?? now()->toDateString();
        $to   = $this->toDate   ?? $from;

        $syncLog = SyncLog::create([
            'type'       => 'attendance',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $zk->syncAttendance($from, $to);

            $syncLog->update([
                'status'          => 'success',
                'records_fetched' => $result['synced'],
                'completed_at'    => now(),
            ]);

            Log::info("SyncAttendanceJob: Synced {$result['synced']} records ({$from} to {$to}).");
        } catch (\Throwable $e) {
            $syncLog->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            Log::error("SyncAttendanceJob: Failed ({$from} to {$to}): " . $e->getMessage());
            throw $e;
        }
    }
}
