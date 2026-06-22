<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.sync')->group(function () {
    Route::post('/sync/attendance', [SyncController::class, 'attendance']);
    Route::post('/sync/employees',  [SyncController::class, 'employees']);
});
