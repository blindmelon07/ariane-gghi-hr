<?php

use App\Jobs\GeneratePayslipsJob;
use App\Livewire\Admin\PayrollProcessor;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SalaryDetail;
use App\Models\User;
use App\Services\PhilippinePayrollService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function payHr(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'hr_admin']);
    $user->syncRoles('hr_admin');
    return $user;
}

function payEmp(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    $emp = Employee::create(array_merge([
        'emp_code'   => "PAY{$n}",
        'first_name' => 'Pay',
        'last_name'  => "Roll{$n}",
        'department' => 'IT',
        'is_active'  => true,
    ], $attrs));

    SalaryDetail::create(['employee_id' => $emp->id, 'rate_type' => 'monthly', 'basic_salary' => 20000]);

    return $emp;
}

function payPeriod(string $status = 'draft'): PayrollPeriod
{
    return PayrollPeriod::create([
        'name'        => 'October 2026 - 1st Half',
        'cutoff_type' => 'semi_monthly_1',
        'start_date'  => '2026-10-01',
        'end_date'    => '2026-10-15',
        'status'      => $status,
    ]);
}

// ── Choosing employees ────────────────────────────────────────────────────────

describe('generate payslips for selected employees', function () {
    it('pre-selects every eligible employee when opened', function () {
        $a = payEmp();
        $b = payEmp();
        Employee::create(['emp_code' => 'NOSAL', 'first_name' => 'No', 'last_name' => 'Salary', 'is_active' => true]);
        $period = payPeriod();

        Livewire::actingAs(payHr())->test(PayrollProcessor::class)
            ->call('openGenerate', $period->id)
            ->assertSet('generatePeriodId', $period->id)
            ->assertSet('selectedEmployeeIds', [(string) $a->id, (string) $b->id])
            ->assertSee('Generate Payslips')
            ->assertSee($a->full_name)
            ->assertDontSee('NOSAL')
            ->assertSee('2 of 2 selected');
    });

    it('dispatches the job with only the selected employees', function () {
        Queue::fake();
        $a = payEmp();
        payEmp();
        $period = payPeriod();

        Livewire::actingAs(payHr())->test(PayrollProcessor::class)
            ->call('openGenerate', $period->id)
            ->set('selectedEmployeeIds', [(string) $a->id])
            ->call('generatePayslips')
            ->assertSet('generatePeriodId', null);

        Queue::assertPushed(GeneratePayslipsJob::class, fn ($job) => $job->payrollPeriodId === $period->id && $job->employeeIds === [$a->id]);
        expect($period->fresh()->status)->toBe('processing');
    });

    it('dispatches for everyone when all employees are selected', function () {
        Queue::fake();
        payEmp();
        payEmp();
        $period = payPeriod();

        Livewire::actingAs(payHr())->test(PayrollProcessor::class)
            ->call('openGenerate', $period->id)
            ->call('generatePayslips');

        Queue::assertPushed(GeneratePayslipsJob::class, fn ($job) => $job->employeeIds === null);
    });

    it('requires at least one employee', function () {
        Queue::fake();
        payEmp();
        $period = payPeriod();

        Livewire::actingAs(payHr())->test(PayrollProcessor::class)
            ->call('openGenerate', $period->id)
            ->set('selectedEmployeeIds', [])
            ->call('generatePayslips')
            ->assertHasErrors('selectedEmployeeIds');

        Queue::assertNothingPushed();
        expect($period->fresh()->status)->toBe('draft');
    });

    it('does not open for a finalized period', function () {
        $period = payPeriod('finalized');

        Livewire::actingAs(payHr())->test(PayrollProcessor::class)
            ->call('openGenerate', $period->id)
            ->assertSet('generatePeriodId', null);
    });
});

// ── Job ───────────────────────────────────────────────────────────────────────

describe('GeneratePayslipsJob', function () {
    it('only writes payslips for the given employees and leaves the rest untouched', function () {
        $a = payEmp();
        $b = payEmp();
        $period = payPeriod('processed');

        $existing = Payslip::create(['employee_id' => $b->id, 'payroll_period_id' => $period->id, 'net_pay' => 123]);

        $service = Mockery::mock(PhilippinePayrollService::class);
        $service->shouldReceive('computePayslip')->once()
            ->withArgs(fn ($emp) => $emp->id === $a->id)
            ->andReturn(['net_pay' => 999]);

        (new GeneratePayslipsJob($period->id, [$a->id]))->handle($service);

        expect(Payslip::where('employee_id', $a->id)->value('net_pay'))->toEqual(999)
            ->and($existing->fresh()->net_pay)->toEqual(123)
            ->and($period->fresh()->status)->toBe('processed');
    });
});
