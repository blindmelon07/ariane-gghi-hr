<?php

use App\Livewire\Employee\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\PhilippinePayrollService;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * HR policy: leave is not capped by credits. Employees regular for at least
 * 1 year earn 2.5 paid days a month (VL and SL); leave beyond the balance is unpaid.
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-01 06:00:00'));
    $this->vl = LeaveType::firstOrCreate(['code' => 'VL'], ['name' => 'Vacation Leave', 'max_days_per_year' => 15]);
    $this->vl->forceFill(['monthly_accrual_days' => 2.5])->save();
});

function plEmployee(?string $regularizedOn, ?string $code = null): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'            => $code ?? "PL{$n}",
        'first_name'          => 'Paid',
        'last_name'           => "Leave{$n}",
        'is_active'           => true,
        'employment_type'     => 'regular',
        'regularization_date' => $regularizedOn,
    ]);
}

function plCredit(Employee $emp, LeaveType $type, float $total): void
{
    LeaveCredit::create([
        'employee_id' => $emp->id, 'leave_type_id' => $type->id, 'year' => 2026,
        'total_credits' => $total, 'used_credits' => 0,
    ]);
}

describe('monthly accrual', function () {
    it('credits only employees regular for at least one year', function () {
        $oneYear  = plEmployee('2025-10-01');
        $tooNew   = plEmployee('2025-10-02');
        $noDate   = plEmployee(null);

        $this->artisan('leave:accrue')->assertSuccessful();

        $credited = fn (Employee $e) => (float) LeaveCredit::where('employee_id', $e->id)
            ->where('leave_type_id', $this->vl->id)->value('total_credits');

        expect($credited($oneYear))->toEqual(2.5)
            ->and($credited($tooNew))->toEqual(0.0)
            ->and($credited($noDate))->toEqual(0.0);
    });

    it('does not mark the month processed when nobody is eligible yet', function () {
        plEmployee(null);

        $this->artisan('leave:accrue')->assertSuccessful();

        expect(\DB::table('leave_accrual_logs')->count())->toBe(0);
    });
});

describe('filing and approval', function () {
    it('allows leave beyond the balance and files the excess unpaid', function () {
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'employee', 'employee_code' => 'PLX']);
        $user->syncRoles('employee');
        $emp = plEmployee('2024-01-01', 'PLX');
        plCredit($emp, $this->vl, 2.5);

        Livewire::actingAs($user)->test(LeaveRequestForm::class)
            ->set('leave_type_id', $this->vl->id)
            ->set('start_date', '2026-10-05')   // Mon
            ->set('end_date', '2026-10-09')     // Fri
            ->set('reason', 'Out of town for the week')
            ->assertSet('paidDays', 2.5)
            ->call('submit')
            ->assertHasNoErrors();

        $leave = LeaveRequest::where('employee_id', $emp->id)->first();
        expect((float) $leave->total_days)->toEqual(5.0)
            ->and((float) $leave->paid_days)->toEqual(2.5);
    });

    it('deducts only the paid days from credits on final approval', function () {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->syncRoles('super_admin');
        $emp = plEmployee('2024-01-01');
        plCredit($emp, $this->vl, 2.5);

        $leave = LeaveRequest::create([
            'employee_id' => $emp->id, 'leave_type_id' => $this->vl->id,
            'start_date' => '2026-10-05', 'end_date' => '2026-10-09', 'total_days' => 5,
            'reason' => 'Trip', 'status' => 'pending', 'approval_step' => 1,
        ]);

        app(LeaveService::class)->overrideApprove($leave, $admin);

        $credit = LeaveCredit::where('employee_id', $emp->id)->first();
        expect((float) $leave->fresh()->paid_days)->toEqual(2.5)
            ->and((float) $credit->used_credits)->toEqual(2.5)
            ->and((float) $credit->remaining_credits)->toEqual(0.0);
    });
});

describe('payroll', function () {
    it('counts only paid leave days, taking them from the start of the leave', function () {
        $emp = plEmployee('2024-01-01');
        LeaveRequest::create([
            'employee_id' => $emp->id, 'leave_type_id' => $this->vl->id,
            'start_date' => '2026-10-13', 'end_date' => '2026-10-19', // Tue → Mon, Sun 18 skipped
            'total_days' => 6, 'paid_days' => 2.5,
            'reason' => 'Trip', 'status' => 'approved',
        ]);

        $paidIn = fn (string $from, string $to) => (fn () => $this->getApprovedLeaveDays($emp->id, $from, $to))
            ->call(app(PhilippinePayrollService::class));

        // 13, 14 fully paid; 15 half paid (rest unpaid) — all inside the first cutoff
        expect($paidIn('2026-10-01', '2026-10-15'))->toEqual(2.5)
            ->and($paidIn('2026-10-16', '2026-10-31'))->toEqual(0.0);
    });
});
