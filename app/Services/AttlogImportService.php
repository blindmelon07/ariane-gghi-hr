<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports a raw ZKTeco device "attlog" export — one row per punch, fields
 * (UserID, Date, Time, Status, Verify, Reserved) split unevenly across
 * columns A/B with literal tab characters, as produced by the device's own
 * export tool (not the "Employee Attendance Record Table" report).
 *
 * Matches purely by numeric device UserID == employees.emp_code, since the
 * file carries no employee names — only device user ids.
 */
class AttlogImportService
{
    /**
     * @return array{matched_employees: int, unmatched_user_ids: array<int, string>, punches_inserted: int}
     */
    public function import(string $filePath): array
    {
        $sheet      = IOFactory::load($filePath)->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $employeesByCode = Employee::whereRaw("emp_code REGEXP '^[0-9]+$'")->get()->keyBy('emp_code');

        $unmatchedUserIds   = [];
        $matchedEmployeeIds = [];
        $inserted           = 0;

        for ($row = 1; $row <= $highestRow; $row++) {
            $colA = explode("\t", (string) $sheet->getCell('A'.$row)->getValue());
            $colB = explode("\t", (string) $sheet->getCell('B'.$row)->getValue());

            $userId = trim($colA[0] ?? '');
            $date   = trim($colA[1] ?? '');
            $time   = trim($colB[0] ?? '');
            $status = (int) ($colB[1] ?? 0);

            if ($userId === '' || $date === '' || $time === '') {
                continue;
            }

            $employee = $employeesByCode->get($userId);

            if (! $employee) {
                $unmatchedUserIds[$userId] = true;
                continue;
            }

            $punchTime = Carbon::parse("{$date} {$time}");

            AttendanceLog::updateOrCreate(
                ['emp_code' => $employee->emp_code, 'punch_time' => $punchTime],
                [
                    'employee_id' => $employee->id,
                    'punch_date'  => $punchTime->toDateString(),
                    'punch_state' => $status,
                ]
            );

            $matchedEmployeeIds[$employee->id] = true;
            $inserted++;
        }

        return [
            'matched_employees'  => count($matchedEmployeeIds),
            'unmatched_user_ids' => array_keys($unmatchedUserIds),
            'punches_inserted'   => $inserted,
        ];
    }
}
