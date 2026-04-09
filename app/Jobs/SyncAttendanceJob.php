<?php

namespace App\Jobs;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\BioTimeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $date = null)
    {
        $this->onQueue('biotime');
    }

    public function handle(BioTimeService $bioTime): void
    {
        $date = $this->date ?? now()->toDateString();

        try {
            $startTime = $date . ' 00:00:00';
            $endTime   = $date . ' 23:59:59';

            $transactions = $bioTime->getTransactions([
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ]);

            $count = 0;

            foreach ($transactions as $txn) {
                $empCode   = $txn['emp_code'] ?? null;
                $punchTime = $txn['punch_time'] ?? null;

                if (!$empCode || !$punchTime) {
                    continue;
                }

                $employee = Employee::where('emp_code', $empCode)->first();
                if (!$employee) {
                    continue;
                }

                $punchCarbon = Carbon::parse($punchTime);

                AttendanceLog::updateOrCreate(
                    [
                        'emp_code'   => $empCode,
                        'punch_time' => $punchCarbon,
                    ],
                    [
                        'employee_id'  => $employee->id,
                        'punch_date'   => $punchCarbon->toDateString(),
                        'punch_state'  => $txn['punch_state'] ?? 0,
                        'verify_type'  => $txn['verify_type'] ?? 0,
                        'terminal_sn'  => $txn['terminal_sn'] ?? null,
                        'is_processed' => false,
                    ]
                );

                $count++;
            }

            Log::info("SyncAttendanceJob: Synced {$count} transactions for {$date}.");
        } catch (\Throwable $e) {
            Log::error("SyncAttendanceJob: Failed for {$date} — " . $e->getMessage());
            throw $e;
        }
    }
}
