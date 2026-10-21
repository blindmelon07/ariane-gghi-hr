<?php

use App\Livewire\Admin\PayrollProcessor;
use App\Livewire\Admin\SalaryDeductionApprovals;
use App\Livewire\Employee\SalaryDeductionForm;
use App\Models\DeductionType;
use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SalaryDeductionRequest;
use App\Models\User;
use App\Services\PhilippinePayrollService;
use App\Services\SalaryDeductionService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 09:00:00', 'Asia/Manila'));
    Storage::fake('local');
});

function sdUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function sdEmployee(string $code): Employee
{
    return Employee::create(['emp_code' => $code, 'first_name' => 'Ded', 'last_name' => $code, 'is_active' => true]);
}

function sdLine(array $overrides = []): array
{
    return array_merge(['particular' => 'gsac_savings', 'description' => '', 'amount' => '500', 'total' => '', 'cutoff' => 'both', 'starts_on' => '2026-10-22'], $overrides);
}

function sdPeriod(string $type, string $start, string $end): PayrollPeriod
{
    return PayrollPeriod::create(['name' => "{$start} – {$end}", 'cutoff_type' => $type, 'start_date' => $start, 'end_date' => $end, 'status' => 'processed']);
}

it('files a request, requiring a total and an amortization table for loans', function () {
    $user = sdUser('employee', 'SD1');
    sdEmployee('SD1');

    Livewire::actingAs($user)->test(SalaryDeductionForm::class)
        ->set('lines', [sdLine(['particular' => 'sss', 'amount' => '1000'])])
        ->call('submit')
        ->assertHasErrors('attachment');

    Livewire::actingAs($user)->test(SalaryDeductionForm::class)
        ->set('lines', [sdLine(['particular' => 'sss', 'amount' => '1000'])])
        ->set('attachment', UploadedFile::fake()->create('amortization.pdf', 50, 'application/pdf'))
        ->call('submit')
        ->assertHasErrors('lines.0.total');

    Livewire::actingAs($user)->test(SalaryDeductionForm::class)
        ->set('lines', [
            sdLine(['particular' => 'sss', 'amount' => '1000', 'total' => '3000']),
            sdLine(['particular' => 'gsac_damayan', 'amount' => '50', 'cutoff' => '2nd']),
        ])
        ->set('attachment', UploadedFile::fake()->create('amortization.pdf', 50, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors();

    $request = SalaryDeductionRequest::with('lines')->sole();
    expect($request->lines)->toHaveCount(2)
        ->and($request->attachment_path)->not->toBeNull()
        ->and($request->totalPerCutoff())->toBe(1050.0);
    Storage::disk('local')->assertExists($request->attachment_path);
});

it('creates the payroll deductions automatically when HR approves', function () {
    $user = sdUser('employee', 'SD2');
    $emp  = sdEmployee('SD2');

    Livewire::actingAs($user)->test(SalaryDeductionForm::class)
        ->set('lines', [
            sdLine(['particular' => 'cash_advance', 'amount' => '1000', 'total' => '2500']),
            sdLine(['particular' => 'others', 'description' => 'Uniform', 'amount' => '200', 'cutoff' => '1st']),
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $request = SalaryDeductionRequest::sole();
    expect(OtherDeduction::count())->toBe(0);   // nothing until approved

    Livewire::actingAs(sdUser('hr_admin'))->test(SalaryDeductionApprovals::class)
        ->call('openAction', $request->id, 'approve')
        ->call('confirmAction')
        ->assertHasNoErrors();

    $cash   = OtherDeduction::where('deduction_type_id', DeductionType::where('code', 'CASH_ADVANCE')->value('id'))->sole();
    $others = OtherDeduction::where('deduction_type_id', DeductionType::where('code', 'OTHERS')->value('id'))->sole();

    expect($cash->employee_id)->toBe($emp->id)
        ->and($cash->auto_stop)->toBeTrue()
        ->and((float) $cash->remaining_balance)->toBe(2500.0)
        ->and($cash->is_active)->toBeTrue()
        ->and($others->auto_stop)->toBeFalse()
        ->and($others->cutoff_schedule)->toBe('1st')
        ->and($others->description)->toContain('Uniform')
        ->and($request->lines()->whereNull('other_deduction_id')->count())->toBe(0);
});

it('rejecting creates no deductions; department heads cannot approve', function () {
    $user = sdUser('employee', 'SD3');
    sdEmployee('SD3');
    Livewire::actingAs($user)->test(SalaryDeductionForm::class)->set('lines', [sdLine()])->call('submit');
    $request = SalaryDeductionRequest::sole();

    Livewire::actingAs(sdUser('department_head'))->test(SalaryDeductionApprovals::class)
        ->call('openAction', $request->id, 'approve')->call('confirmAction')
        ->assertHasErrors('remarks');

    Livewire::actingAs(sdUser('hr_admin'))->test(SalaryDeductionApprovals::class)
        ->call('openAction', $request->id, 'reject')->set('remarks', 'Wrong amount')->call('confirmAction');

    expect($request->fresh()->status)->toBe('rejected')->and(OtherDeduction::count())->toBe(0);
});

it('starts on the chosen date and draws a loan down until it stops by itself', function () {
    $emp  = sdEmployee('SD4');
    $loan = OtherDeduction::create([
        'employee_id' => $emp->id, 'description' => 'Cash advance', 'amount_per_cutoff' => 1000,
        'cutoff_schedule' => 'both', 'starts_on' => '2026-11-01', 'remaining_balance' => 2500, 'auto_stop' => true, 'is_active' => true,
    ]);

    $payroll = app(PhilippinePayrollService::class);
    $service = app(SalaryDeductionService::class);

    $oct2 = sdPeriod('semi_monthly_2', '2026-10-16', '2026-10-31');
    expect($payroll->applicableDeductions($emp, $oct2))->toHaveCount(0);   // not started yet

    $amounts = [];
    foreach ([['semi_monthly_1', '2026-11-01', '2026-11-15'], ['semi_monthly_2', '2026-11-16', '2026-11-30'], ['semi_monthly_1', '2026-12-01', '2026-12-15'], ['semi_monthly_2', '2026-12-16', '2026-12-31']] as [$type, $start, $end]) {
        $period = sdPeriod($type, $start, $end);
        Payslip::create(['employee_id' => $emp->id, 'payroll_period_id' => $period->id]);
        $amounts[] = $payroll->applicableDeductions($emp, $period)->sum(fn ($d) => $d->amountThisCutoff());
        $service->recordPeriod($period);
        $service->recordPeriod($period);   // finalizing twice never double-counts
    }

    $loan->refresh();
    expect($amounts)->toEqual([1000.0, 1000.0, 500.0, 0])
        ->and((float) $loan->remaining_balance)->toBe(0.0)
        ->and($loan->is_active)->toBeFalse()
        ->and($loan->payments()->count())->toBe(3);
});

it('finalizing a payroll period draws down balances', function () {
    $emp  = sdEmployee('SD5');
    $loan = OtherDeduction::create([
        'employee_id' => $emp->id, 'description' => 'GSAC loan', 'amount_per_cutoff' => 700,
        'cutoff_schedule' => 'both', 'remaining_balance' => 7000, 'auto_stop' => true, 'is_active' => true,
    ]);
    $period = sdPeriod('semi_monthly_1', '2026-10-01', '2026-10-15');
    Payslip::create(['employee_id' => $emp->id, 'payroll_period_id' => $period->id]);

    Livewire::actingAs(sdUser('hr_admin'))->test(PayrollProcessor::class)->call('finalize', $period->id);

    expect($period->fresh()->status)->toBe('finalized')
        ->and((float) $loan->fresh()->remaining_balance)->toBe(6300.0);
});

it('leaves manually entered deductions untouched on finalize', function () {
    $emp    = sdEmployee('SD6');
    $manual = OtherDeduction::create([
        'employee_id' => $emp->id, 'description' => 'Manual', 'amount_per_cutoff' => 300,
        'cutoff_schedule' => 'both', 'remaining_balance' => 900, 'is_active' => true,
    ]);
    $period = sdPeriod('semi_monthly_1', '2026-10-01', '2026-10-15');
    Payslip::create(['employee_id' => $emp->id, 'payroll_period_id' => $period->id]);

    app(SalaryDeductionService::class)->recordPeriod($period);

    expect((float) $manual->fresh()->remaining_balance)->toBe(900.0)->and($manual->fresh()->is_active)->toBeTrue();
});

it('shows the form and attachment only to the employee and HR', function () {
    $user = sdUser('employee', 'SD7');
    sdEmployee('SD7');
    Livewire::actingAs($user)->test(SalaryDeductionForm::class)
        ->set('lines', [sdLine(['particular' => 'pagibig', 'amount' => '800', 'total' => '8000'])])
        ->set('attachment', UploadedFile::fake()->create('table.pdf', 20, 'application/pdf'))
        ->call('submit');
    $request = SalaryDeductionRequest::sole();

    $this->actingAs($user)->get("/salary-deduction/{$request->id}")->assertOk()->assertSee('SALARY DEDUCTION FORM')->assertSee('Pag-IBIG Loan');
    $this->actingAs($user)->get("/salary-deduction/{$request->id}/attachment")->assertOk();
    $this->actingAs(sdUser('hr_admin'))->get("/salary-deduction/{$request->id}")->assertOk();
    $this->actingAs(sdUser('employee', 'SD8'))->get("/salary-deduction/{$request->id}")->assertForbidden();
    $this->actingAs(sdUser('employee', 'SD9'))->get('/salary-deduction')->assertOk();
});
