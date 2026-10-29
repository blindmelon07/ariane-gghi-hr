<?php

use App\Livewire\Admin\DayOffManager;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\User;
use App\Services\DayOffScheduleImportService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function dsiEmp(string $first, string $last, array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'        => "DSI{$n}",
        'first_name'      => $first,
        'last_name'       => $last,
        'department'      => 'Business Office',
        'is_active'       => true,
        'employment_type' => 'regular',
        'weekday_off'     => 6,
    ], $attrs));
}

/**
 * Builds a sheet shaped like HR's "SCHEDULE DAY OFF" form: title + effective
 * date, weekday header, a merged section banner, and name cells (one holding
 * two names on separate lines).
 */
function dsiSheet(): string
{
    $book  = new Spreadsheet();
    $sheet = $book->getActiveSheet();

    $sheet->setCellValue('C1', 'SCHEDULE DAY OFF');
    $sheet->setCellValue('C2', 'Effective October 12, 2026');
    $sheet->fromArray(['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], null, 'A4');
    $sheet->setCellValue('A5', 'BUSINESS OFFICE');
    $sheet->mergeCells('A5:G5');
    $sheet->fromArray(['Credit and Collection', '', '', '', 'John Paul Ermino', '', ''], null, 'A6');
    $sheet->fromArray(['Claims', 'Dalene Lusuegro', 'Janice Escarcha', "Ailyn Jaylo\nMaricor Derilo", '', '', 'Roger Ducay, Jr.'], null, 'A7');
    $sheet->fromArray(['Billing- HD', '', 'Nobody Known', '', '', '', ''], null, 'A8');

    $path = tempnam(sys_get_temp_dir(), 'dsi') . '.xlsx';
    (new Xlsx($book))->save($path);

    return $path;
}

// ── Parsing ───────────────────────────────────────────────────────────────────

describe('parse', function () {
    it('reads the effective date, weekdays, sections and matches names', function () {
        $ermino = dsiEmp('John Paul', 'Ermino');
        $jaylo  = dsiEmp('Ailyn', 'Jaylo');
        $derilo = dsiEmp('Maricor', 'Derilo');
        $ducay  = dsiEmp('Roger', 'Ducay');
        dsiEmp('Dalene', 'Lusuegro');
        dsiEmp('Janice', 'Escarcha');

        $result  = app(DayOffScheduleImportService::class)->parse(dsiSheet());
        $byName  = collect($result['entries'])->keyBy('name');

        expect($result['effective_date'])->toBe('2026-10-12')
            ->and($result['entries'])->toHaveCount(7)
            ->and($byName['John Paul Ermino'])->toMatchArray(['employee_id' => $ermino->id, 'weekday' => 4, 'section' => 'Credit and Collection'])
            ->and($byName['Ailyn Jaylo'])->toMatchArray(['employee_id' => $jaylo->id, 'weekday' => 3, 'section' => 'Claims'])
            ->and($byName['Maricor Derilo'])->toMatchArray(['employee_id' => $derilo->id, 'weekday' => 3])
            ->and($byName['Roger Ducay, Jr.'])->toMatchArray(['employee_id' => $ducay->id, 'weekday' => 6])
            ->and($byName['Nobody Known'])->toMatchArray(['employee_id' => null, 'problem' => 'No matching employee']);
    });

    it('flags an employee listed on two different days', function () {
        dsiEmp('Janice', 'Escarcha');
        dsiEmp('Dalene', 'Lusuegro');
        $path = dsiSheet();

        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $book->getActiveSheet()->setCellValue('F6', 'Janice Escarcha');
        (new Xlsx($book))->save($path);

        $entries = collect(app(DayOffScheduleImportService::class)->parse($path)['entries'])->where('name', 'Janice Escarcha');

        expect($entries)->toHaveCount(2)
            ->and($entries->pluck('problem')->unique()->all())->toBe(['Listed on more than one day']);
    });

    it('does not match on surname alone when the first name differs', function () {
        dsiEmp('Jessa', 'Ermino');

        $entry = collect(app(DayOffScheduleImportService::class)->parse(dsiSheet())['entries'])->firstWhere('name', 'John Paul Ermino');

        expect($entry['employee_id'])->toBeNull();
    });

    it('matches a sheet name that is the tail of the full name', function () {
        $emp = dsiEmp('Alyssa', 'Dalene Lusuegro');

        $entry = collect(app(DayOffScheduleImportService::class)->parse(dsiSheet())['entries'])->firstWhere('name', 'Dalene Lusuegro');

        expect($entry['employee_id'])->toBe($emp->id);
    });

    it('matches names regardless of accents', function () {
        $emp = dsiEmp('Lui', 'Quiñones');
        $path = dsiSheet();

        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $book->getActiveSheet()->setCellValue('C8', 'Lui Quinones');
        (new Xlsx($book))->save($path);

        $entry = collect(app(DayOffScheduleImportService::class)->parse($path)['entries'])->firstWhere('name', 'Lui Quinones');

        expect($entry['employee_id'])->toBe($emp->id);
    });
});

// ── Applying ──────────────────────────────────────────────────────────────────

describe('apply', function () {
    it('sets weekday_off and creates rest days on that weekday only', function () {
        $emp = dsiEmp('Ailyn', 'Jaylo');

        $result = app(DayOffScheduleImportService::class)->apply(
            [['employee_id' => $emp->id, 'weekday' => 3, 'problem' => null]],
            '2026-10-12', '2026-10-31'
        );

        $dates = DayOff::where('employee_id', $emp->id)->orderBy('date')->pluck('date')->map->toDateString()->all();

        expect($emp->fresh()->weekday_off)->toBe(3)
            ->and($result)->toMatchArray(['employees' => 1, 'created' => 3])
            ->and($dates)->toBe(['2026-10-14', '2026-10-21', '2026-10-28']);
    });

    it('replaces recurring rest days on the old weekday but keeps Sundays and one-off day offs', function () {
        $emp = dsiEmp('Ailyn', 'Jaylo');
        DayOff::create(['employee_id' => $emp->id, 'date' => '2026-10-17', 'type' => 'rest_day', 'is_recurring' => true, 'recurring_day_of_week' => 6]); // old Saturday
        DayOff::create(['employee_id' => $emp->id, 'date' => '2026-10-18', 'type' => 'rest_day', 'is_recurring' => true, 'recurring_day_of_week' => 0]); // Sunday
        DayOff::create(['employee_id' => $emp->id, 'date' => '2026-10-24', 'type' => 'special', 'is_recurring' => false]);                              // one-off

        $result = app(DayOffScheduleImportService::class)->apply(
            [['employee_id' => $emp->id, 'weekday' => 3, 'problem' => null]],
            '2026-10-12', '2026-10-25'
        );

        $dates = DayOff::where('employee_id', $emp->id)->orderBy('date')->pluck('date')->map->toDateString()->all();

        expect($result['removed'])->toBe(1)
            ->and($dates)->toBe(['2026-10-14', '2026-10-18', '2026-10-21', '2026-10-24']);
    });

    it('skips entries that have a problem', function () {
        $emp = dsiEmp('Janice', 'Escarcha');

        $result = app(DayOffScheduleImportService::class)->apply(
            [['employee_id' => $emp->id, 'weekday' => 2, 'problem' => 'Listed on more than one day']],
            '2026-10-12', '2026-10-31'
        );

        expect($result['employees'])->toBe(0)
            ->and($emp->fresh()->weekday_off)->toBe(6)
            ->and(DayOff::count())->toBe(0);
    });

    it('does not set weekday_off for probationary staff but still creates their rest days', function () {
        $emp = dsiEmp('Maricor', 'Derilo', ['employment_type' => 'probationary', 'weekday_off' => null]);

        app(DayOffScheduleImportService::class)->apply(
            [['employee_id' => $emp->id, 'weekday' => 3, 'problem' => null]],
            '2026-10-12', '2026-10-18'
        );

        expect($emp->fresh()->weekday_off)->toBeNull()
            ->and(DayOff::where('employee_id', $emp->id)->count())->toBe(1);
    });
});

// ── Livewire flow ─────────────────────────────────────────────────────────────

it('uploads, previews and applies the schedule from the day-off page', function () {
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $hr = User::factory()->create(['role' => 'hr_admin']);
    $hr->syncRoles('hr_admin');

    $emp  = dsiEmp('John Paul', 'Ermino');
    $file = UploadedFile::fake()->createWithContent('schedule.xlsx', file_get_contents(dsiSheet()));

    Livewire::actingAs($hr)
        ->test(DayOffManager::class)
        ->call('openImport')
        ->set('importFile', $file)
        ->call('readImportFile')
        ->assertHasNoErrors()
        ->assertSet('importDateFrom', '2026-10-12')
        ->assertSee('John Paul Ermino')
        ->set('importDateTo', '2026-10-31')
        ->call('applyImport')
        ->assertSet('showImportModal', false);

    expect($emp->fresh()->weekday_off)->toBe(4)
        ->and(DayOff::where('employee_id', $emp->id)->count())->toBe(3); // Oct 15, 22, 29
});
