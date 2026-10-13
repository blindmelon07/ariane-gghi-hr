<?php

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\HrAlert;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config(['services.unisms.secret_key' => 'sk_test', 'services.unisms.sender_id' => 'GGHI']);
    Http::fake(['unismsapi.com/*' => Http::response(['total' => 1, 'queued' => 1, 'skipped' => [], 'bulk_id' => 'b', 'message' => ['reference_id' => 'm']], 200)]);
});

function appEmployee(array $attrs = []): array
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['employee_code' => (string) (500 + $n), 'role' => 'employee']);
    $emp  = Employee::create($attrs + [
        'emp_code'   => (string) (500 + $n),
        'first_name' => "Emp{$n}",
        'last_name'  => 'Test',
        'is_active'  => true,
        'user_id'    => $user->id,
    ]);

    return [$emp, $user];
}

it('puts payslip notices in the bell once, even without a mobile number', function () {
    [$emp, $user] = appEmployee(['cell_number' => null]);
    $period = PayrollPeriod::create(['name' => 'Oct 1st Half', 'cutoff_type' => 'custom', 'start_date' => '2026-09-26', 'end_date' => '2026-10-10', 'status' => 'finalized']);
    Payslip::create(['employee_id' => $emp->id, 'payroll_period_id' => $period->id]);

    $first  = app(SmsNotificationService::class)->payslipNotices($period);
    $second = app(SmsNotificationService::class)->payslipNotices($period);

    expect($first['app'])->toBe(1)->and($second['app'])->toBe(0);
    expect($user->notifications()->count())->toBe(1);
    expect($user->notifications()->first()->data)
        ->toMatchArray(['title' => 'Payslip ready', 'type' => 'payslip', 'context_key' => "payslip:{$period->id}"]);
});

it('sends overtime decisions to the app', function () {
    Notification::fake();
    [$emp, $user] = appEmployee();
    $ot = OvertimeRequest::create(['employee_id' => $emp->id, 'date' => '2026-10-05', 'requested_hours' => 2, 'reason' => 'x', 'status' => 'rejected', 'remarks' => 'No budget']);

    app(SmsNotificationService::class)->overtimeDecision($ot);

    Notification::assertSentTo($user, HrAlert::class, fn ($n, $channels) => $n->title === 'Overtime rejected'
        && str_contains($n->body, 'No budget')
        && in_array(WebPushChannel::class, $channels, true));
});

it('broadcasts announcements to the app with names filled in', function () {
    [$a, $userA] = appEmployee();
    [$b, $userB] = appEmployee(['cell_number' => '09171234567']);

    $result = app(SmsNotificationService::class)->broadcast(collect([$a, $b]), 'Hi {first_name}, meeting at 3 PM.', 'announcement');

    expect($result['app'])->toBe(2)
        ->and($userA->notifications()->first()->data['message'])->toBe("Hi {$a->first_name}, meeting at 3 PM.")
        ->and($userB->notifications()->first()->data['title'])->toBe('Announcement');
});

it('skips employees without a login account', function () {
    $emp = Employee::create(['emp_code' => '999', 'first_name' => 'No', 'last_name' => 'Account', 'is_active' => true]);

    $result = app(SmsNotificationService::class)->broadcast(collect([$emp]), 'Hello everyone!', 'announcement');

    expect($result['app'])->toBe(0);
});

it('saves and removes a device subscription', function () {
    [, $user] = appEmployee();
    $payload = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'pub', 'auth' => 'tok'], 'contentEncoding' => 'aes128gcm'];

    $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertOk(); // same device again

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->postJson(route('push.unsubscribe'), ['endpoint' => $payload['endpoint']])->assertOk();
    expect(PushSubscription::count())->toBe(0);
});

it('moves a device to whoever logs in on it', function () {
    [, $first]  = appEmployee();
    [, $second] = appEmployee();
    $payload = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/shared', 'keys' => ['p256dh' => 'pub', 'auth' => 'tok']];

    $this->actingAs($first)->postJson(route('push.subscribe'), $payload)->assertOk();
    $this->actingAs($second)->postJson(route('push.subscribe'), $payload)->assertOk();

    expect(PushSubscription::sole()->user_id)->toBe($second->id);
});

it('requires login to subscribe', function () {
    $this->postJson(route('push.subscribe'), ['endpoint' => 'https://x.test/1', 'keys' => ['p256dh' => 'a', 'auth' => 'b']])
        ->assertUnauthorized();
});

it('does nothing for push when VAPID keys are missing', function () {
    config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);
    [, $user] = appEmployee();
    PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://x.test/1', 'endpoint_hash' => hash('sha256', 'https://x.test/1'), 'public_key' => 'a', 'auth_token' => 'b']);

    $user->notify(new HrAlert('Title', 'Body text here'));

    expect($user->notifications()->count())->toBe(1);   // bell still works
});
