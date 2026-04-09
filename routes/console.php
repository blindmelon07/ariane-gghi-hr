<?php

use App\Jobs\SyncAttendanceJob;
use App\Jobs\SyncEmployeesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncAttendanceJob)->everyFifteenMinutes();
Schedule::job(new SyncEmployeesJob)->dailyAt('00:00');
