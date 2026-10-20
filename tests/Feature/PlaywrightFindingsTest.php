<?php

use App\Jobs\SyncEmployeesJob;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Employee\LeaveRequestForm;
use App\Livewire\Employee\TimeCorrectionForm;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function findingsUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

// ── Admin dashboard quick actions ─────────────────────────────────────────────

it('hides HR-only quick actions from managers', function () {
    $this->actingAs(findingsUser('manager'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Process Payroll')
        ->assertDontSee('Sync BioTime Employees');
});

it('shows HR-only quick actions to hr admins', function () {
    $this->actingAs(findingsUser('hr_admin'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Process Payroll')
        ->assertSee('Sync BioTime Employees');
});

it('forbids non-HR roles from dispatching a BioTime sync', function (string $role) {
    Queue::fake();

    Livewire::actingAs(findingsUser($role))
        ->test(Dashboard::class)
        ->call('syncBioTime')
        ->assertForbidden();

    Queue::assertNotPushed(SyncEmployeesJob::class);
})->with(['manager', 'department_head', 'approver']);

it('lets hr admins dispatch a BioTime sync', function () {
    Queue::fake();

    Livewire::actingAs(findingsUser('hr_admin'))
        ->test(Dashboard::class)
        ->call('syncBioTime');

    Queue::assertPushed(SyncEmployeesJob::class);
});

// ── Mobile nav ────────────────────────────────────────────────────────────────

it('does not link security guards to employee-only pages', function () {
    $this->actingAs(findingsUser('security_guard'))
        ->get(route('security.fleet-return'))
        ->assertOk()
        ->assertDontSee(route('payslips.index'))
        ->assertDontSee(route('leave.request'));
});

// ── Deep-link date params ─────────────────────────────────────────────────────

it('ignores a malformed date deep-link on the leave form', function (string $date) {
    $this->actingAs(findingsUser('employee'))
        ->get(route('leave.request', ['type' => 'SL', 'date' => $date]))
        ->assertOk();
})->with(['null', 'garbage', '2026-13-45']);

it('still prefills a valid date deep-link', function () {
    Livewire::withQueryParams(['date' => '2030-01-15'])
        ->actingAs(findingsUser('employee'))
        ->test(LeaveRequestForm::class)
        ->assertSet('start_date', '2030-01-15')
        ->assertSet('end_date', '2030-01-15');

    Livewire::withQueryParams(['date' => 'null'])
        ->actingAs(findingsUser('employee'))
        ->test(TimeCorrectionForm::class)
        ->assertSet('date', '');
});
