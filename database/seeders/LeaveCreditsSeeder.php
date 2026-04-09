<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveCreditsSeeder extends Seeder
{
    public function run(): void
    {
        $year       = now()->year;
        $leaveTypes = LeaveType::all();
        $employees  = Employee::where('is_active', true)->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                LeaveCredit::updateOrCreate(
                    [
                        'employee_id'   => $employee->id,
                        'leave_type_id' => $type->id,
                        'year'          => $year,
                    ],
                    [
                        'total_credits' => $type->max_days_per_year,
                        'used_credits'  => 0,
                    ]
                );
            }
        }
    }
}
