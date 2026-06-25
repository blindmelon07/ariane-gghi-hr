<?php

use App\Http\Controllers\PayslipController;
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

Route::middleware(['auth', 'role:employee'])->group(function () {
    Route::view('payslips', 'payslips.index')->name('payslips.index');
    Route::view('overtime/request', 'overtime.request')->name('overtime.request');
});

// Leave filing — employees + managers/dept heads (managers skip to step 3 automatically)
Route::middleware(['auth', 'role:employee,manager,department_head'])->group(function () {
    Route::view('leave/request', 'leave.request')->name('leave.request');
    Route::view('leave/my-requests', 'leave.my-requests')->name('leave.my-requests');
    Route::view('leave/balance', 'leave.balance')->name('leave.balance');
    Route::view('time-correction', 'time-correction')->name('time-correction.request');
});

// ── Shared ────────────────────────────────────────────────────────────────────

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

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
    Route::view('admin/time-corrections', 'admin.time-corrections')->name('admin.time-corrections');
    Route::view('admin/reports/attendance', 'admin.reports.attendance')->name('admin.reports.attendance');
    Route::view('admin/reports/leave', 'admin.reports.leave')->name('admin.reports.leave');
});

// ── HR Admin + Super Admin routes ─────────────────────────────────────────────

Route::middleware(['auth', 'role:super_admin,hr_admin'])->group(function () {
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
    Route::view('admin/leave/credits', 'admin.leave-credits')->name('admin.leave-credits');
    Route::view('admin/overtime', 'admin.overtime')->name('admin.overtime');
    Route::view('admin/reports/payroll', 'admin.reports.payroll')->name('admin.reports.payroll');
    Route::get('admin/payslips/{employee}/{period}/download', [PayslipController::class, 'adminDownload'])
        ->name('admin.payslips.download');
});

// ── Super Admin only ──────────────────────────────────────────────────────────

Route::view('admin/roles-permissions', 'admin.roles-permissions')
    ->middleware(['auth', 'role:super_admin'])
    ->name('admin.roles-permissions');

require __DIR__.'/auth.php';
