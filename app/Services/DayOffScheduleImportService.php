<?php

namespace App\Services;

use App\Models\DayOff;
use App\Models\DayOffSwap;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the HR "SCHEDULE DAY OFF" sheet — a weekly grid of rest days.
 *
 * Layout (first sheet):
 *   - a title area above the grid holding "Effective <date>"
 *   - a header row with weekday names (Monday … Saturday) across columns
 *   - section banner rows (e.g. "BUSINESS OFFICE"), ignored
 *   - one or more rows per section: column A is the section name, each weekday
 *     cell holds the employee name(s) whose rest day is that weekday; a cell may
 *     list several names on separate lines
 *
 * parse() only reads and matches; apply() writes. The two are split so the
 * admin can review the name matches before anything is saved.
 */
class DayOffScheduleImportService
{
    private const WEEKDAYS = [
        'sunday' => 0, 'sun' => 0,
        'monday' => 1, 'mon' => 1,
        'tuesday' => 2, 'tue' => 2, 'tues' => 2,
        'wednesday' => 3, 'wed' => 3,
        'thursday' => 4, 'thu' => 4, 'thurs' => 4,
        'friday' => 5, 'fri' => 5,
        'saturday' => 6, 'sat' => 6,
    ];

    private const NAME_SUFFIXES = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];

    /**
     * @return array{
     *     effective_date: ?string,
     *     entries: array<int, array{name: string, section: string, weekday: int, employee_id: ?int, employee_name: ?string, emp_code: ?string, problem: ?string}>
     * }
     */
    public function parse(string $filePath): array
    {
        $rows = IOFactory::load($filePath)->getActiveSheet()->toArray(null, true, true, false);

        $headerIndex = null;
        $dayColumns  = [];
        foreach ($rows as $index => $row) {
            $found = [];
            foreach ($row as $col => $cell) {
                $label = Str::lower(trim((string) $cell));
                if (isset(self::WEEKDAYS[$label])) {
                    $found[$col] = self::WEEKDAYS[$label];
                }
            }
            if (count($found) >= 3) {
                $headerIndex = $index;
                $dayColumns  = $found;
                break;
            }
        }

        if ($headerIndex === null) {
            return ['effective_date' => null, 'entries' => []];
        }

        $employees = $this->employeeIndex();
        $entries   = [];
        $section   = '';

        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $label = trim((string) ($row[0] ?? ''));

            // Section banners span the whole row: one value, no names under weekdays.
            $dayValues = array_filter(array_map(fn ($col) => trim((string) ($row[$col] ?? '')), array_keys($dayColumns)));
            if ($label === '' && count($dayValues) === 1 && Str::upper(reset($dayValues)) === reset($dayValues)) {
                continue;
            }
            if ($label !== '') {
                $section = $label;
            }

            foreach ($dayColumns as $col => $weekday) {
                foreach ($this->splitNames((string) ($row[$col] ?? '')) as $name) {
                    $employee  = $this->matchEmployee($name, $employees);
                    $entries[] = [
                        'name'          => $name,
                        'section'       => $section,
                        'weekday'       => $weekday,
                        'employee_id'   => $employee?->id,
                        'employee_name' => $employee?->full_name,
                        'emp_code'      => $employee?->emp_code,
                        'problem'       => $employee ? null : 'No matching employee',
                    ];
                }
            }
        }

        // One person can only have one weekly rest day.
        $byEmployee = collect($entries)->whereNotNull('employee_id')->groupBy('employee_id');
        foreach ($entries as &$entry) {
            if ($entry['employee_id'] && $byEmployee[$entry['employee_id']]->pluck('weekday')->unique()->count() > 1) {
                $entry['problem'] = 'Listed on more than one day';
            }
        }
        unset($entry);

        return [
            'effective_date' => $this->findEffectiveDate(array_slice($rows, 0, $headerIndex)),
            'entries'        => $entries,
        ];
    }

    /**
     * Sets each employee's weekly rest day and lays down rest-day records for
     * [$from, $until]. Recurring rest days already generated on another weekday
     * in that range are removed, since the new schedule replaces them. One-off
     * day offs (holidays, swaps, manual entries) are left alone.
     *
     * @param array<int, array{employee_id: ?int, weekday: int, problem: ?string}> $entries
     * @return array{employees: int, created: int, removed: int}
     */
    public function apply(array $entries, string $from, string $until, ?int $createdBy = null): array
    {
        $weekdayByEmployee = collect($entries)
            ->filter(fn ($e) => $e['employee_id'] && ! $e['problem'])
            ->mapWithKeys(fn ($e) => [$e['employee_id'] => (int) $e['weekday']]);

        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($until)->startOfDay();
        $note  = 'Day-off schedule effective ' . $start->format('M d, Y');

        $swappedWorkdays = DayOffSwap::approved()
            ->whereIn('employee_id', $weekdayByEmployee->keys())
            ->whereDate('original_date', '>=', $start->toDateString())
            ->whereDate('original_date', '<=', $end->toDateString())
            ->get(['employee_id', 'original_date'])
            ->map(fn ($s) => $s->employee_id . '|' . $s->original_date->toDateString())
            ->all();

        $created = 0;
        $removed = 0;

        DB::transaction(function () use ($weekdayByEmployee, $start, $end, $note, $swappedWorkdays, $createdBy, &$created, &$removed) {
            foreach (Employee::whereIn('id', $weekdayByEmployee->keys())->get() as $employee) {
                $weekday = $weekdayByEmployee[$employee->id];

                // Probationary staff are Sunday-only by policy; their sheet day is
                // still honoured through the day-off records below.
                if (($employee->employment_type ?? 'regular') !== 'probationary') {
                    $employee->update(['weekday_off' => $weekday]);
                }

                $removed += DayOff::where('employee_id', $employee->id)
                    ->where('type', 'rest_day')
                    ->where('is_recurring', true)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->whereNotIn('recurring_day_of_week', [0, $weekday])
                    ->delete();

                $existing = DayOff::where('employee_id', $employee->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->pluck('date')
                    ->map(fn ($d) => $d->toDateString())
                    ->flip();

                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $date = $d->toDateString();
                    if ($d->dayOfWeek !== $weekday
                        || isset($existing[$date])
                        || in_array($employee->id . '|' . $date, $swappedWorkdays, true)) {
                        continue;
                    }

                    DayOff::create([
                        'employee_id'           => $employee->id,
                        'date'                  => $date,
                        'type'                  => 'rest_day',
                        'description'           => $note,
                        'is_recurring'          => true,
                        'recurring_day_of_week' => $weekday,
                        'created_by'            => $createdBy,
                    ]);
                    $created++;
                }
            }
        });

        return ['employees' => $weekdayByEmployee->count(), 'created' => $created, 'removed' => $removed];
    }

    /**
     * @return array<int, string>
     */
    private function splitNames(string $cell): array
    {
        // Several names in one cell sit on separate lines (or are joined by / ; &).
        // Commas are kept: they belong to suffixes like "Ducay, Jr.".
        return array_values(array_filter(
            array_map('trim', preg_split('/\R|\s*[\/;&]\s*/u', $cell)),
            fn ($name) => $name !== '' && preg_match('/\pL/u', $name)
        ));
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function findEffectiveDate(array $rows): ?string
    {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (preg_match('/effective\s*(?:date)?\s*:?\s*(.+)/i', (string) $cell, $m)) {
                    try {
                        return Carbon::parse(trim($m[1]))->toDateString();
                    } catch (\Throwable) {
                        return null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return Collection<int, array{employee: Employee, first: string, last: string, full: string}>
     */
    private function employeeIndex(): Collection
    {
        return Employee::where('is_active', true)->get()->map(fn (Employee $e) => [
            'employee' => $e,
            'first'    => $this->normalize($e->first_name),
            'last'     => $this->normalize($e->last_name),
            'full'     => $this->normalize($e->first_name . ' ' . $e->last_name),
        ]);
    }

    private function matchEmployee(string $name, Collection $employees): ?Employee
    {
        $normalized = $this->normalize($name);
        $tokens     = explode(' ', $normalized);

        // 1. Exact "first last" match.
        $exact = $employees->where('full', $normalized);
        if ($exact->count() === 1) {
            return $exact->first()['employee'];
        }

        if (count($tokens) < 2) {
            return null;
        }

        // 2. The sheet uses the tail of the full name (e.g. second given name + surname).
        $byTail = $employees->filter(fn ($e) => Str::endsWith($e['full'], ' ' . $normalized));
        if ($byTail->count() === 1) {
            return $byTail->first()['employee'];
        }

        // 3. Same surname and a compatible first name ("Jac" ~ "Jacqueline"). The first
        //    name must agree even when the surname is unique, so "Janel Deuna" never
        //    lands on "Jessa Deuna".
        $first = $tokens[0];
        $byLast = $employees->filter(function ($e) use ($normalized, $tokens, $first) {
            $sameLast  = $e['last'] !== '' && ($e['last'] === end($tokens) || Str::endsWith($normalized, ' ' . $e['last']));
            $firstWord = Str::before($e['first'], ' ');

            return $sameLast && $firstWord !== '' && (Str::startsWith($firstWord, $first) || Str::startsWith($first, $firstWord));
        });

        return $byLast->count() === 1 ? $byLast->first()['employee'] : null;
    }

    private function normalize(?string $value): string
    {
        $value  = Str::lower(Str::ascii((string) $value));
        $value  = preg_replace('/[^a-z\s]/', ' ', $value);
        $tokens = array_filter(preg_split('/\s+/', $value), fn ($t) => $t !== '' && ! in_array($t, self::NAME_SUFFIXES, true));

        return implode(' ', $tokens);
    }
}
