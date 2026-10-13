<?php

use App\Livewire\Admin\SmsCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['services.unisms.secret_key' => 'sk_test', 'services.unisms.sender_id' => 'GGHI']);
    Http::fake([
        'unismsapi.com/api/account' => Http::response(['sms_credits' => 500], 200),
        'unismsapi.com/api/bulk'    => Http::response(['total' => 1, 'queued' => 1, 'skipped' => [], 'bulk_id' => 'b'], 200),
    ]);
    foreach (['hr_admin', 'employee'] as $r) {
        Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
});

function smsAdmin(): User
{
    $u = User::factory()->create(['role' => 'hr_admin']);
    $u->syncRoles('hr_admin');
    return $u;
}

it('lets HR open the SMS Center and blocks employees', function () {
    $this->actingAs(smsAdmin())->get(route('admin.sms'))->assertOk()->assertSee('SMS credits');

    $emp = User::factory()->create(['role' => 'employee']);
    $emp->syncRoles('employee');
    $this->actingAs($emp)->get(route('admin.sms'))->assertForbidden();
});

it('sends a personalised announcement to one department', function () {
    $hd  = Department::create(['name' => 'Hemodialysis', 'is_active' => true]);
    $adm = Department::create(['name' => 'Administration', 'is_active' => true]);
    Employee::create(['emp_code' => '1', 'first_name' => 'Ana', 'last_name' => 'Cruz', 'is_active' => true, 'department_id' => $hd->id, 'cell_number' => '09171234567']);
    Employee::create(['emp_code' => '2', 'first_name' => 'Ben', 'last_name' => 'Reyes', 'is_active' => true, 'department_id' => $adm->id, 'cell_number' => '09181234567']);

    Livewire::actingAs(smsAdmin())
        ->test(SmsCenter::class)
        ->assertSet('credits', 500)
        ->set('audience', 'department')
        ->set('departmentId', $hd->id)
        ->set('smsMessage', 'Hi {first_name}, meeting at 3 PM.')
        ->call('send')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => str_ends_with($req->url(), '/bulk')
        && count($req['messages']) === 1
        && $req['messages'][0]['content'] === 'Hi Ana, meeting at 3 PM.');
    expect(SmsLog::sole()->type)->toBe('announcement');
});

it('requires a message', function () {
    Livewire::actingAs(smsAdmin())
        ->test(SmsCenter::class)
        ->set('smsMessage', '')
        ->call('send')
        ->assertHasErrors('smsMessage');
});

it('rejects messages shorter than 10 characters', function () {
    Livewire::actingAs(smsAdmin())
        ->test(SmsCenter::class)
        ->set('audience', 'numbers')
        ->set('customNumbers', '09171234567')
        ->set('smsMessage', 'test')
        ->call('send')
        ->assertHasErrors(['smsMessage' => 'min']);

    Http::assertNotSent(fn ($req) => str_ends_with($req->url(), '/bulk'));
});

it('sends a test notification to every user with a registered device', function () {
    $withDevice = User::factory()->create(['role' => 'employee']);
    $noDevice   = User::factory()->create(['role' => 'employee']);
    \App\Models\PushSubscription::create([
        'user_id' => $withDevice->id, 'endpoint' => 'https://push.test/1', 'endpoint_hash' => hash('sha256', 'https://push.test/1'),
        'public_key' => 'k', 'auth_token' => 't',
    ]);

    Livewire::actingAs(smsAdmin())
        ->test(SmsCenter::class)
        ->set('tab', 'push')
        ->assertSee('Send Test to All Devices')
        ->call('sendTestToAllDevices');

    expect($withDevice->notifications()->sole()->data['title'])->toBe('Test notification')
        ->and($noDevice->notifications()->count())->toBe(0);
    Http::assertNotSent(fn ($req) => str_ends_with($req->url(), '/bulk') || str_ends_with($req->url(), '/sms'));
});
