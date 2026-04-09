<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'VL', 'name' => 'Vacation Leave',  'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true],
            ['code' => 'SL', 'name' => 'Sick Leave',      'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true],
            ['code' => 'EL', 'name' => 'Emergency Leave',  'max_days_per_year' => 3,  'is_paid' => true, 'requires_approval' => true],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
