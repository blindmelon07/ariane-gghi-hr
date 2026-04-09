<?php

use App\Http\Controllers\PayslipController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Employee dashboard
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'role:employee'])
    ->name('dashboard');

// HR Admin dashboard
Route::view('admin/dashboard', 'admin.dashboard')
    ->middleware(['auth', 'role:hr_admin,manager'])
    ->name('admin.dashboard');

// Manager dashboard
Route::view('manager/dashboard', 'manager.dashboard')
    ->middleware(['auth', 'role:manager'])
    ->name('manager.dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Employee Leave Routes
Route::middleware(['auth', 'role:employee'])->group(function () {
    Route::view('leave/request', 'leave.request')->name('leave.request');
    Route::view('leave/my-requests', 'leave.my-requests')->name('leave.my-requests');
    Route::view('leave/balance', 'leave.balance')->name('leave.balance');
    Route::view('payslips', 'payslips.index')->name('payslips.index');
});

// Payslip download (any authenticated user — controller checks ownership)
Route::get('payslips/{payslip}/download', [PayslipController::class, 'download'])
    ->middleware(['auth'])
    ->name('payslips.download');

// Admin/Manager Leave Routes
Route::view('admin/leave', 'admin.leave')
    ->middleware(['auth', 'role:hr_admin,manager'])
    ->name('admin.leave');

Route::view('admin/leave/credits', 'admin.leave-credits')
    ->middleware(['auth', 'role:hr_admin'])
    ->name('admin.leave-credits');

// Admin Payroll Routes
Route::middleware(['auth', 'role:hr_admin'])->group(function () {
    Route::view('admin/payroll', 'admin.payroll')->name('admin.payroll');
    Route::view('admin/payroll/salary', 'admin.salary')->name('admin.salary');
    Route::view('admin/employees', 'admin.employees')->name('admin.employees');
    Route::view('admin/deductions', 'admin.deductions')->name('admin.deductions');
    Route::view('admin/reports/payroll', 'admin.reports.payroll')->name('admin.reports.payroll');
});

// Report Routes (HR Admin + Manager)
Route::middleware(['auth', 'role:hr_admin,manager'])->group(function () {
    Route::view('admin/reports/attendance', 'admin.reports.attendance')->name('admin.reports.attendance');
    Route::view('admin/reports/leave', 'admin.reports.leave')->name('admin.reports.leave');
});

require __DIR__.'/auth.php';
