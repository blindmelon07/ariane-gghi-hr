<?php

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config([
        'services.unisms.secret_key' => 'sk_test',
        'services.unisms.sender_id'  => 'GGHI',
        'app.url'                    => 'https://hr.example.com',
    ]);
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
});

function smsEmployee(array $attrs = []): Employee
{
    static $n = 0;
    $n++;

    return Employee::create($attrs + [
        'emp_code'        => (string) (100 + $n),
        'first_name'      => "First{$n}",
        'last_name'       => "Last{$n}",
        'is_active'       => true,
        'employment_type' => 'regular',
        'department'      => 'Administration',
        'cell_number'     => '0917123' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
    ]);
}

it('texts payslip notices once per period', function () {
    Http::fake(['unismsapi.com/api/bulk' => Http::response(['total' => 2, 'queued' => 2, 'skipped' => [], 'bulk_id' => 'b1'], 200)]);

    $period = PayrollPeriod::create(['name' => 'October 2026 - 1st Half', 'cutoff_type' => 'custom', 'start_date' => '2026-09-26', 'end_date' => '2026-10-10', 'status' => 'finalized']);
    foreach ([smsEmployee(), smsEmployee()] as $e) {
        Payslip::create(['employee_id' => $e->id, 'payroll_period_id' => $period->id]);
    }

    $first = app(SmsNotificationService::class)->payslipNotices($period);
    expect($first)->toMatchArray(['queued' => 2, 'already' => 0]);
    Http::assertSent(fn ($req) => str_contains($req['messages'][0]['content'], 'October 2026 - 1st Half'));

    $second = app(SmsNotificationService::class)->payslipNotices($period);
    expect($second)->toMatchArray(['queued' => 0, 'already' => 2]);
    Http::assertSentCount(1);
});

it('texts the overtime decision', function () {
    Http::fake(['unismsapi.com/api/sms' => Http::response(['message' => ['reference_id' => 'm1']], 201)]);
    $e = smsEmployee();

    $ot = OvertimeRequest::create(['employee_id' => $e->id, 'date' => '2026-10-05', 'requested_hours' => 2, 'reason' => 'x', 'status' => 'approved', 'approved_hours' => 1.5]);

    expect(app(SmsNotificationService::class)->overtimeDecision($ot))->toBeTrue();
    Http::assertSent(fn ($req) => str_contains($req['content'], 'APPROVED (1.5 hr)'));
    expect(SmsLog::sole()->type)->toBe('overtime');
});

it('creates an account, texts the login and stores only a masked copy', function () {
    Http::fake(['unismsapi.com/api/sms' => Http::response(['message' => ['reference_id' => 'm1']], 201)]);
    $e = smsEmployee();

    $result = app(SmsNotificationService::class)->sendCredentials(collect([$e]));

    expect($result['sent'])->toHaveCount(1);
    $sentPassword = null;
    Http::assertSent(function ($req) use (&$sentPassword, $e) {
        preg_match('/Password: (\S+)/', $req['content'], $m);
        $sentPassword = $m[1] ?? null;
        return str_contains($req['content'], "Employee Code: {$e->emp_code}")
            && ! str_contains($req['content'], 'http');
    });
    expect($sentPassword)->toHaveLength(8);

    $user = User::where('employee_code', $e->emp_code)->sole();
    expect(Hash::check($sentPassword, $user->password))->toBeTrue()
        ->and($e->fresh()->user_id)->toBe($user->id)
        ->and(SmsLog::sole()->content)->toContain('********')->not->toContain($sentPassword);
});

it('keeps the old password when the login text fails', function () {
    Http::fake(['unismsapi.com/*' => Http::response(['message' => 'No credits'], 402)]);
    $e    = smsEmployee();
    $user = User::factory()->create(['employee_code' => $e->emp_code, 'password' => 'old-password', 'is_active' => true]);
    $e->update(['user_id' => $user->id]);

    $result = app(SmsNotificationService::class)->sendCredentials(collect([$e->fresh('user')]));

    expect($result['failed'])->toHaveCount(1)
        ->and(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('skips employees without a valid number for login details', function () {
    Http::fake();
    $e = smsEmployee(['cell_number' => '991959607']);

    $result = app(SmsNotificationService::class)->sendCredentials(collect([$e]));

    expect($result['failed'][0])->toContain('no valid mobile number');
    Http::assertNothingSent();
    expect(User::count())->toBe(0);
});

it('alerts late and absent staff once, skipping nurses and rest days', function () {
    Http::fake(['unismsapi.com/api/bulk' => Http::response(['total' => 2, 'queued' => 2, 'skipped' => [], 'bulk_id' => 'b'], 200)]);
    $date = '2026-10-13'; // Tuesday

    $late = smsEmployee();
    foreach (['08:20:00', '12:00:00', '13:00:00', '17:00:00'] as $t) {
        AttendanceLog::create(['employee_id' => $late->id, 'emp_code' => $late->emp_code, 'punch_time' => "{$date} {$t}", 'punch_date' => $date, 'punch_state' => 0]);
    }
    smsEmployee();                                       // absent
    smsEmployee(['department' => 'Nursing']);            // rotating shifts — excluded
    $resting = smsEmployee();                            // has a day-off record — not absent
    DayOff::create(['employee_id' => $resting->id, 'date' => $date, 'type' => 'rest_day']);

    $result = app(SmsNotificationService::class)->attendanceAlerts($date);

    expect($result['late'])->toBe(1)
        ->and($result['absent'])->toBe(1);
    Http::assertSent(fn ($req) => collect($req['messages'])->contains(fn ($m) => str_contains($m['content'], 'LATE') && str_contains($m['content'], '20 minute')));

    $again = app(SmsNotificationService::class)->attendanceAlerts($date);
    expect($again['late'])->toBe(0)->and($again['absent'])->toBe(0);
});

it('sends nothing on Sunday', function () {
    Http::fake();
    smsEmployee();

    $result = app(SmsNotificationService::class)->attendanceAlerts('2026-10-11');

    expect($result['absent'])->toBe(0);
    Http::assertNothingSent();
});
