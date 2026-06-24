<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccrueMonthlyLeaveCredits extends Command
{
    protected $signature = 'leave:accrue
                            {--month= : Month to accrue (1-12, default: current month)}
                            {--year=  : Year to accrue (default: current year)}
                            {--force  : Run even if already accrued for this month}
                            {--triggered-by=auto : Who triggered this (auto or manual)}';

    protected $description = 'Add monthly leave credits (VL/SL) to all active employees';

    public function handle(): int
    {
        $month       = (int) ($this->option('month') ?: now()->month);
        $year        = (int) ($this->option('year')  ?: now()->year);
        $triggeredBy = $this->option('triggered-by') ?? 'auto';

        $alreadyRan = DB::table('leave_accrual_logs')
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($alreadyRan && ! $this->option('force')) {
            $this->warn("Leave accrual for {$year}-{$month} already processed. Use --force to override.");

            return self::FAILURE;
        }

        $leaveTypes = LeaveType::whereNotNull('monthly_accrual_days')
            ->where('monthly_accrual_days', '>', 0)
            ->get();

        if ($leaveTypes->isEmpty()) {
            $this->warn('No leave types have monthly_accrual_days configured.');

            return self::FAILURE;
        }

        $employees = Employee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            $this->warn('No active employees found.');

            return self::FAILURE;
        }

        $this->info("Accruing {$year}-{$month} for {$employees->count()} employees...");

        DB::transaction(function () use ($employees, $leaveTypes, $year, $month, $triggeredBy) {
            foreach ($employees as $employee) {
                foreach ($leaveTypes as $type) {
                    LeaveCredit::firstOrCreate(
                        [
                            'employee_id'   => $employee->id,
                            'leave_type_id' => $type->id,
                            'year'          => $year,
                        ],
                        ['total_credits' => 0, 'used_credits' => 0]
                    );

                    LeaveCredit::where('employee_id', $employee->id)
                        ->where('leave_type_id', $type->id)
                        ->where('year', $year)
                        ->increment('total_credits', (float) $type->monthly_accrual_days);
                }
            }

            DB::table('leave_accrual_logs')->upsert(
                [
                    'year'           => $year,
                    'month'          => $month,
                    'accrual_days'   => $leaveTypes->first()->monthly_accrual_days,
                    'employee_count' => $employees->count(),
                    'triggered_by'   => $triggeredBy,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ],
                ['year', 'month'],
                ['accrual_days', 'employee_count', 'triggered_by', 'updated_at']
            );
        });

        $summary = $leaveTypes->map(fn ($t) => "{$t->code} +{$t->monthly_accrual_days}d")->join(', ');
        $this->info("Done — {$summary} credited to {$employees->count()} employees.");

        return self::SUCCESS;
    }
}
