<?php

use App\Http\Controllers\GatePassController;
use App\Http\Controllers\OvertimeFormController;
use App\Http\Controllers\PatientTransportController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SalaryDeductionController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

/*
|--------------------------------------------------------------------------
| Role Reference
|--------------------------------------------------------------------------
| employee       – Regular staff (leave filing, payslips)
| department_head – Step-1 leave approver (same menu as manager)
| manager        – Step-1 leave approver
| hr_admin       – Step-2 leave approver + full HR access
| approver       – Step-3 leave approver (Medical Director)
| super_admin    – Step-3 leave approver (CEO) + all access
| head_nurse     – Nursing dept duty roster only + standard employee self-service
|                   (no leave/trip-ticket approval powers)
|
| NOTE: Two role layers exist:
|   1. users.role column  – drives sidebar menu & dashboardRoute()
|   2. Spatie role        – drives route middleware (role:xxx)
|   Both must match. The UserFactory and createAccount() keep them in sync.
*/

// ── Employee routes ───────────────────────────────────────────────────────────

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'role:employee'])
    ->name('dashboard');

Route::middleware(['auth', 'role:employee,head_nurse'])->group(function () {
    Route::view('payslips', 'payslips.index')->name('payslips.index');
    Route::view('overtime/request', 'overtime.request')->name('overtime.request');
});

// Leave filing — employees + managers/dept heads (managers skip to step 3 automatically)
Route::middleware(['auth', 'role:employee,manager,department_head,head_nurse'])->group(function () {
    Route::view('leave/request', 'leave.request')->name('leave.request');
    Route::view('leave/my-requests', 'leave.my-requests')->name('leave.my-requests');
    Route::view('leave/balance', 'leave.balance')->name('leave.balance');
    Route::view('time-correction', 'time-correction')->name('time-correction.request');
    Route::view('day-off-swap', 'day-off-swap')->name('day-off-swap.request');
    Route::view('shift-change', 'shift-change')->name('shift-change.request');
    Route::view('undertime', 'undertime')->name('undertime.request');
});

// Fleet — Trip ticket filing (all roles except pure approvers)
Route::middleware(['auth', 'role:employee,manager,department_head,hr_admin,super_admin,head_nurse'])->group(function () {
    Route::view('trip-ticket', 'trip-ticket')->name('trip-ticket.request');
    Route::view('trip-ticket/my-tickets', 'trip-ticket-list')->name('trip-ticket.list');
});

// ── Head Nurse — Nursing department duty roster ───────────────────────────────

Route::view('head-nurse/roster', 'head-nurse.roster')
    ->middleware(['auth', 'role:head_nurse'])
    ->name('head-nurse.roster');

// ── Shared ────────────────────────────────────────────────────────────────────

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

Route::get('overtime/form/{overtimeForm}', [OvertimeFormController::class, 'show'])
    ->middleware(['auth'])
    ->name('overtime.show');

// Salary deduction form — every employee files; HR approval creates the payroll deductions
Route::middleware(['auth'])->group(function () {
    Route::view('salary-deduction', 'salary-deduction')->name('salary-deduction.request');
    Route::get('salary-deduction/{salaryDeduction}', [SalaryDeductionController::class, 'show'])->name('salary-deduction.show');
    Route::get('salary-deduction/{salaryDeduction}/attachment', [SalaryDeductionController::class, 'attachment'])->name('salary-deduction.attachment');
});

Route::view('admin/salary-deductions', 'admin.salary-deductions')
    ->middleware(['auth', 'role:super_admin,hr_admin'])
    ->name('admin.salary-deductions');

// Gate pass — every employee files; HR approves; the guard checks the approved pass
Route::middleware(['auth'])->group(function () {
    Route::view('gate-pass', 'gate-pass')->name('gate-pass.request');
    Route::get('gate-pass/{gatePass}', [GatePassController::class, 'show'])->name('gate-pass.show');
});

Route::view('admin/gate-passes', 'admin.gate-passes')
    ->middleware(['auth', 'role:super_admin,hr_admin'])
    ->name('admin.gate-passes');

// Patient transport slip — filed by Drivers and Institutional Workers only
// (checked by position inside the form), approved by HR, logged by the guard
Route::middleware(['auth'])->group(function () {
    Route::view('patient-transport', 'patient-transport')->name('patient-transport.request');
    Route::get('patient-transport/{patientTransport}', [PatientTransportController::class, 'show'])->name('patient-transport.show');
});

Route::view('admin/patient-transports', 'admin.patient-transports')
    ->middleware(['auth', 'role:super_admin,hr_admin'])
    ->name('admin.patient-transports');

Route::middleware(['auth'])->group(function () {
    Route::post('push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
    Route::post('push/test', [PushSubscriptionController::class, 'test'])->middleware('throttle:5,1')->name('push.test');
});

Route::get('payslips/{payslip}/download', [PayslipController::class, 'download'])
    ->middleware(['auth'])
    ->name('payslips.download');

// ── Admin dashboard (all non-employee roles) ──────────────────────────────────

Route::view('admin/dashboard', 'admin.dashboard')
    ->middleware(['auth', 'role:super_admin,hr_admin,manager,department_head,approver'])
    ->name('admin.dashboard');

// ── Leave approvals + time corrections + reports (all approver roles) ────────

Route::middleware(['auth', 'role:super_admin,hr_admin,manager,department_head,approver'])->group(function () {
    Route::view('admin/leave', 'admin.leave')->name('admin.leave');
    Route::view('admin/overtime', 'admin.overtime')->name('admin.overtime');   // Dept Head → HR → CEO/MD
    Route::view('admin/time-corrections', 'admin.time-corrections')->name('admin.time-corrections');
    Route::view('admin/day-off-swaps', 'admin.day-off-swaps')->name('admin.day-off-swaps');
    Route::view('admin/shift-changes', 'admin.shift-changes')->name('admin.shift-changes');
    Route::view('admin/undertime', 'admin.undertime')->name('admin.undertime');
    Route::view('admin/reports/attendance', 'admin.reports.attendance')->name('admin.reports.attendance');
    Route::view('admin/reports/leave', 'admin.reports.leave')->name('admin.reports.leave');
    Route::view('admin/fleet/requests', 'admin.fleet-requests')->name('admin.fleet-requests');
});

// ── HR Admin + Super Admin routes ─────────────────────────────────────────────

Route::middleware(['auth', 'role:super_admin,hr_admin'])->group(function () {
    Route::view('admin/fleet/vehicles', 'admin.fleet-vehicles')->name('admin.fleet-vehicles');
    Route::view('admin/fleet/drivers', 'admin.fleet-drivers')->name('admin.fleet-drivers');
    Route::view('admin/holidays', 'admin.holidays')->name('admin.holidays');
    Route::view('admin/employees', 'admin.employees')->name('admin.employees');
    Route::view('admin/departments', 'admin.departments')->name('admin.departments');
    Route::view('admin/positions', 'admin.positions')->name('admin.positions');
    Route::view('admin/payroll', 'admin.payroll')->name('admin.payroll');
    Route::view('admin/payroll/salary', 'admin.salary')->name('admin.salary');
    Route::view('admin/deductions', 'admin.deductions')->name('admin.deductions');
    Route::view('admin/day-offs', 'admin.day-offs')->name('admin.day-offs');
    Route::view('admin/schedules', 'admin.schedules')->name('admin.schedules');
    Route::view('admin/biometrics', 'admin.biometrics')->name('admin.biometrics');
    Route::view('admin/sms', 'admin.sms')->name('admin.sms');
    Route::view('admin/leave/credits', 'admin.leave-credits')->name('admin.leave-credits');
    Route::view('admin/reports/payroll', 'admin.reports.payroll')->name('admin.reports.payroll');
    Route::view('admin/reports/overtime', 'admin.reports.overtime')->name('admin.reports.overtime');
    Route::view('admin/reports/late', 'admin.reports.late')->name('admin.reports.late');
    Route::get('admin/payslips/{employee}/{period}/download', [PayslipController::class, 'adminDownload'])
        ->name('admin.payslips.download');
});

// ── Security Guard ────────────────────────────────────────────────────────────

Route::view('security/fleet-return', 'security.fleet-return')
    ->middleware(['auth', 'role:security_guard,hr_admin,super_admin'])
    ->name('security.fleet-return');

// ── Super Admin only ──────────────────────────────────────────────────────────

Route::view('admin/roles-permissions', 'admin.roles-permissions')
    ->middleware(['auth', 'role:super_admin'])
    ->name('admin.roles-permissions');

Route::view('admin/accounts', 'admin.accounts')
    ->middleware(['auth', 'role:super_admin,hr_admin'])
    ->name('admin.accounts');

require __DIR__.'/auth.php';
