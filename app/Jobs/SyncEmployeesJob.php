<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Services\ActivityLogService;
use App\Services\BioTimeService;
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

    public function handle(BioTimeService $bioTime): void
    {
        try {
            $bioEmployees = $bioTime->getEmployees();

            $syncedCodes = [];

            foreach ($bioEmployees as $emp) {
                $empCode = $emp['emp_code'] ?? null;
                if (!$empCode) {
                    continue;
                }

                Employee::updateOrCreate(
                    ['emp_code' => $empCode],
                    [
                        'first_name'  => $emp['first_name'] ?? '',
                        'last_name'   => $emp['last_name'] ?? '',
                        'department'  => $emp['department']['dept_name'] ?? $emp['department'] ?? null,
                        'position'    => $emp['position']['position_name'] ?? $emp['position'] ?? null,
                        'hire_date'   => $emp['hire_date'] ?? null,
                        'is_active'   => true,
                        'biotime_id'  => $emp['id'] ?? null,
                        'synced_at'   => now(),
                    ]
                );

                $syncedCodes[] = $empCode;
            }

            // Mark employees not in BioTime as inactive
            Employee::whereNotIn('emp_code', $syncedCodes)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            Log::info('SyncEmployeesJob: Successfully synced ' . count($syncedCodes) . ' employees.');
            ActivityLogService::log('employees_synced', 'Synced ' . count($syncedCodes) . ' employees from BioTime.');
        } catch (\Throwable $e) {
            Log::error('SyncEmployeesJob: Failed — ' . $e->getMessage());
            throw $e;
        }
    }
}
