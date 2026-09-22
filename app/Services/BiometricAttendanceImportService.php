<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Imports ZKTeco "Employee Attendance Record Table" exports (legacy .xls,
 * one sheet named "RecordTable") into attendance_logs.
 *
 * Layout: repeating per-employee blocks —
 *   row N:   a "UserID:" label + device user id, and a "Name:" label + name
 *   row N+1: day-of-month header across columns 2..17 (16 cutoff days)
 *   row N+2..: one or more punch rows; each cell holds 1-2 "HH:MM" times
 *              separated by a literal newline (one line per punch)
 * Multiple punch rows per block represent AM/PM sessions; punches within a
 * day are read top-to-bottom and alternate in/out.
 */
class BiometricAttendanceImportService
{
    private const DATE_COL_START = 2;

    private const DATE_COL_END = 17;

    private const LABEL_SEARCH_COLS = 15;

    /**
     * @param string $periodStart Y-m-d date of the sheet's first day-of-month column;
     *                            supplies the year/month the "day" cells roll forward from.
     * @return array{employees_matched: int, employees_unmatched: array<int, string>, punches_inserted: int}
     */
    public function import(string $filePath, string $periodStart): array
    {
        $sheet = IOFactory::load($filePath)->getActiveSheet();

        $blocks = $this->splitIntoBlocks($sheet);

        $matched      = 0;
        $unmatched    = [];
        $punchesTotal = 0;

        foreach ($blocks as $block) {
            $employee = $this->matchEmployee($block['name']);

            if (! $employee) {
                $unmatched[] = $block['name'];
                continue;
            }

            $matched++;
            $punchesTotal += $this->importBlockPunches($employee, $sheet, $block, $periodStart);
        }

        return [
            'employees_matched'   => $matched,
            'employees_unmatched' => $unmatched,
            'punches_inserted'    => $punchesTotal,
        ];
    }

    /**
     * @return array<int, array{name: string, dateRow: int, punchRows: array<int, int>}>
     */
    private function splitIntoBlocks(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $labelRows = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $name = $this->readBlockName($sheet, $row);
            if ($name !== null) {
                $labelRows[] = ['row' => $row, 'name' => $name];
            }
        }

        $blocks = [];
        foreach ($labelRows as $i => $label) {
            $dateRow  = $label['row'] + 1;
            $nextRow  = $labelRows[$i + 1]['row'] ?? ($highestRow + 1);
            $punchRows = range($dateRow + 1, $nextRow - 1);

            $blocks[] = [
                'name'      => $label['name'],
                'dateRow'   => $dateRow,
                'punchRows' => $punchRows,
            ];
        }

        return $blocks;
    }

    private function readBlockName(Worksheet $sheet, int $row): ?string
    {
        $userIdCol = null;
        $nameCol   = null;

        for ($col = 1; $col <= self::LABEL_SEARCH_COLS; $col++) {
            $value = trim((string) ($sheet->getCell([$col, $row])->getValue() ?? ''));

            if (preg_match('/^user\s*id:?$/i', $value)) {
                $userIdCol = $col;
            }
            if (preg_match('/^name:?$/i', $value)) {
                $nameCol = $col;
            }
        }

        if ($userIdCol === null || $nameCol === null) {
            return null;
        }

        $name = trim((string) ($sheet->getCell([$nameCol + 1, $row])->getValue() ?? ''));

        return $name !== '' ? $name : null;
    }

    private function matchEmployee(string $deviceName): ?Employee
    {
        $tokens = array_values(array_filter(preg_split('/\s+/', trim($deviceName))));

        if (count($tokens) >= 2) {
            $lastToken  = Str::lower(end($tokens));
            $firstWords = Str::lower(implode(' ', array_slice($tokens, 0, -1)));

            $candidates = Employee::whereRaw('LOWER(TRIM(last_name)) = ?', [$lastToken])->get();

            if ($candidates->count() === 1) {
                return $candidates->first();
            }

            if ($candidates->count() > 1) {
                $firstFirstWord = Str::before($firstWords, ' ');
                $narrowed       = $candidates->filter(
                    fn (Employee $e) => Str::startsWith(Str::lower($e->first_name), $firstFirstWord)
                );

                return $narrowed->count() === 1 ? $narrowed->first() : null;
            }
        }

        // Single token (nickname-style device entry): only accept an unambiguous match.
        $token = Str::lower($tokens[0] ?? '');
        if ($token === '') {
            return null;
        }

        $byFirst = Employee::whereRaw('LOWER(TRIM(first_name)) = ?', [$token])->get();
        if ($byFirst->count() === 1) {
            return $byFirst->first();
        }

        $byLast = Employee::whereRaw('LOWER(TRIM(last_name)) = ?', [$token])->get();

        return $byLast->count() === 1 ? $byLast->first() : null;
    }

    private function importBlockPunches(Employee $employee, Worksheet $sheet, array $block, string $periodStart): int
    {
        $dateByCol = $this->resolveDatesForRow($sheet, $block['dateRow'], $periodStart);
        $inserted  = 0;

        foreach ($dateByCol as $col => $date) {
            $times = [];
            foreach ($block['punchRows'] as $row) {
                $raw = (string) ($sheet->getCell([$col, $row])->getValue() ?? '');
                foreach (explode("\n", $raw) as $line) {
                    $line = trim($line);
                    if (preg_match('/^\d{1,2}:\d{2}$/', $line)) {
                        $times[] = $line;
                    }
                }
            }

            foreach (array_values($times) as $index => $time) {
                $punchTime = Carbon::parse("{$date} {$time}");

                AttendanceLog::updateOrCreate(
                    ['emp_code' => $employee->emp_code, 'punch_time' => $punchTime],
                    [
                        'employee_id' => $employee->id,
                        'punch_date'  => $date,
                        'punch_state' => $index % 2 === 0 ? 0 : 1,
                    ]
                );
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * @return array<int, string> column index => Y-m-d date, for the 16-day cutoff window
     */
    private function resolveDatesForRow(Worksheet $sheet, int $dateRow, string $periodStart): array
    {
        $start   = Carbon::parse($periodStart);
        $year    = $start->year;
        $month   = $start->month;
        $prevDay = 0;
        $dates   = [];

        for ($col = self::DATE_COL_START; $col <= self::DATE_COL_END; $col++) {
            $day = (int) $sheet->getCell([$col, $dateRow])->getValue();
            if ($day === 0) {
                continue;
            }
            if ($day < $prevDay) {
                $month++;
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
            }
            $prevDay = $day;

            $dates[$col] = Carbon::create($year, $month, $day)->toDateString();
        }

        return $dates;
    }
}
