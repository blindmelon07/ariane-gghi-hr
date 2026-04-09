<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            // Admin
            ['name' => 'Admin Day',            'department' => 'Admin',               'time_in' => '08:00', 'time_out' => '17:00', 'break_start' => '12:00', 'break_end' => '13:00', 'description' => '12:00-13:00 Lunch Break'],

            // Nursing
            ['name' => 'Nursing Day 12h',      'department' => 'Nursing',             'time_in' => '07:00', 'time_out' => '19:00', 'is_night_shift' => false],
            ['name' => 'Nursing Night 12h',    'department' => 'Nursing',             'time_in' => '19:00', 'time_out' => '07:00', 'is_night_shift' => true],
            ['name' => 'Nursing Morning 8h',   'department' => 'Nursing',             'time_in' => '07:00', 'time_out' => '15:00'],
            ['name' => 'Nursing Day 9h',       'department' => 'Nursing',             'time_in' => '07:00', 'time_out' => '16:00'],
            ['name' => 'Nursing Day 13h',      'department' => 'Nursing',             'time_in' => '07:00', 'time_out' => '20:00'],
            ['name' => 'Nursing Mid 9h',       'department' => 'Nursing',             'time_in' => '11:00', 'time_out' => '20:00'],

            // Laboratory
            ['name' => 'Lab Day 12h',          'department' => 'Laboratory',          'time_in' => '07:00', 'time_out' => '19:00'],
            ['name' => 'Lab Night 12h',        'department' => 'Laboratory',          'time_in' => '19:00', 'time_out' => '07:00', 'is_night_shift' => true],
            ['name' => 'Lab Morning 8h',       'department' => 'Laboratory',          'time_in' => '07:00', 'time_out' => '15:00'],
            ['name' => 'Lab Afternoon 8h',     'department' => 'Laboratory',          'time_in' => '15:00', 'time_out' => '23:00'],
            ['name' => 'Lab Graveyard 8h',     'department' => 'Laboratory',          'time_in' => '23:00', 'time_out' => '07:00', 'is_night_shift' => true],
            ['name' => 'Lab Day 8h',           'department' => 'Laboratory',          'time_in' => '08:00', 'time_out' => '16:00'],

            // Radiology
            ['name' => 'Radiology Day 8h',     'department' => 'Radiology',           'time_in' => '08:00', 'time_out' => '16:00'],
            ['name' => 'Radiology Morning 8h', 'department' => 'Radiology',           'time_in' => '07:00', 'time_out' => '15:00'],
            ['name' => 'Radiology Day 12h',    'department' => 'Radiology',           'time_in' => '07:00', 'time_out' => '19:00'],
            ['name' => 'Radiology Day 11h',    'department' => 'Radiology',           'time_in' => '08:00', 'time_out' => '19:00'],
            ['name' => 'Radiology Day 10h',    'department' => 'Radiology',           'time_in' => '08:00', 'time_out' => '18:00'],
            ['name' => 'Radiology Day 9h',     'department' => 'Radiology',           'time_in' => '08:00', 'time_out' => '17:00'],

            // Hospital Pharmacy
            ['name' => 'Hosp Pharm Day 12h',   'department' => 'Hospital Pharmacy',   'time_in' => '07:00', 'time_out' => '19:00'],
            ['name' => 'Hosp Pharm Night 12h', 'department' => 'Hospital Pharmacy',   'time_in' => '19:00', 'time_out' => '07:00', 'is_night_shift' => true],
            ['name' => 'Hosp Pharm Afternoon',  'department' => 'Hospital Pharmacy',   'time_in' => '15:00', 'time_out' => '23:00'],
            ['name' => 'Hosp Pharm Morning 8h', 'department' => 'Hospital Pharmacy',  'time_in' => '07:00', 'time_out' => '15:00'],
            ['name' => 'Hosp Pharm Graveyard',  'department' => 'Hospital Pharmacy',   'time_in' => '23:00', 'time_out' => '07:00', 'is_night_shift' => true],

            // Community Pharmacy (split shifts)
            ['name' => 'Comm Pharm Split A',   'department' => 'Community Pharmacy',  'time_in' => '08:00', 'time_out' => '11:00', 'time_in_2' => '12:00', 'time_out_2' => '17:00', 'description' => '8-11, 12-17'],
            ['name' => 'Comm Pharm Split B',   'department' => 'Community Pharmacy',  'time_in' => '09:00', 'time_out' => '12:00', 'time_in_2' => '13:00', 'time_out_2' => '18:00', 'description' => '9-12, 13-18'],
            ['name' => 'Comm Pharm Split C',   'department' => 'Community Pharmacy',  'time_in' => '07:00', 'time_out' => '11:00', 'time_in_2' => '12:00', 'time_out_2' => '16:00', 'description' => '7-11, 12-16'],
            ['name' => 'Comm Pharm Split D',   'department' => 'Community Pharmacy',  'time_in' => '08:00', 'time_out' => '12:00', 'time_in_2' => '14:00', 'time_out_2' => '18:00', 'description' => '8-12, 14-18'],
            ['name' => 'Comm Pharm Split E',   'department' => 'Community Pharmacy',  'time_in' => '07:00', 'time_out' => '11:00', 'time_in_2' => '12:00', 'time_out_2' => '18:00', 'description' => '7-11, 12-18'],
            ['name' => 'Comm Pharm Split F',   'department' => 'Community Pharmacy',  'time_in' => '08:00', 'time_out' => '12:00', 'time_in_2' => '13:00', 'time_out_2' => '17:00', 'description' => '8-12, 13-17'],
            ['name' => 'Comm Pharm Split G',   'department' => 'Community Pharmacy',  'time_in' => '11:00', 'time_out' => '13:00', 'time_in_2' => '15:00', 'time_out_2' => '21:00', 'description' => '11-13, 15-21'],
            ['name' => 'Comm Pharm Split H',   'department' => 'Community Pharmacy',  'time_in' => '06:00', 'time_out' => '10:00', 'time_in_2' => '11:00', 'time_out_2' => '15:00', 'description' => '6-10, 11-15'],
            ['name' => 'Comm Pharm Split I',   'department' => 'Community Pharmacy',  'time_in' => '06:00', 'time_out' => '12:00', 'time_in_2' => '13:00', 'time_out_2' => '15:00', 'description' => '6-12, 13-15'],
            ['name' => 'Comm Pharm Split J',   'department' => 'Community Pharmacy',  'time_in' => '10:00', 'time_out' => '13:00', 'time_in_2' => '14:00', 'time_out_2' => '19:00', 'description' => '10-13, 14-19'],

            // HD Technician
            ['name' => 'HD Tech Day 10h',      'department' => 'HD Technician',       'time_in' => '06:00', 'time_out' => '16:00'],
            ['name' => 'HD Tech Mid 10h',      'department' => 'HD Technician',       'time_in' => '10:00', 'time_out' => '20:00'],
            ['name' => 'HD Tech Early 9h',     'department' => 'HD Technician',       'time_in' => '05:00', 'time_out' => '14:00'],
            ['name' => 'HD Tech Day 9h',       'department' => 'HD Technician',       'time_in' => '08:00', 'time_out' => '17:00'],
        ];

        foreach ($schedules as $schedule) {
            Schedule::firstOrCreate(
                ['name' => $schedule['name'], 'department' => $schedule['department']],
                array_merge([
                    'is_night_shift' => false,
                    'break_start'    => null,
                    'break_end'      => null,
                    'time_in_2'      => null,
                    'time_out_2'     => null,
                    'description'    => null,
                ], $schedule)
            );
        }
    }
}
