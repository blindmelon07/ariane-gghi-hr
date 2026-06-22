<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync attendance from ZKTeco device every 15 minutes
Schedule::command('sync:attendance')->everyFifteenMinutes()->withoutOverlapping();

// Sync employees from ZKTeco device once daily at midnight
Schedule::command('sync:employees')->dailyAt('00:00')->withoutOverlapping();
