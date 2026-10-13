<?php

namespace App\Console\Commands;

use App\Services\SmsNotificationService;
use Illuminate\Console\Command;

class SendAttendanceAlertsCommand extends Command
{
    protected $signature = 'sms:attendance-alerts {--date= : Day to check (Y-m-d), defaults to today} {--force : Run even when UNISMS_ATTENDANCE_ALERTS is off}';
    protected $description = 'Text employees who were late or absent on a given day';

    public function handle(SmsNotificationService $notifier): int
    {
        if (! config('services.unisms.attendance_alerts') && ! $this->option('force')) {
            $this->info('Attendance alerts are disabled (set UNISMS_ATTENDANCE_ALERTS=true to enable).');
            return self::SUCCESS;
        }

        $date   = $this->option('date') ?? now()->toDateString();
        $result = $notifier->attendanceAlerts($date);

        $this->info("{$date}: {$result['late']} late, {$result['absent']} absent — {$result['queued']} sent, {$result['skipped']} skipped, {$result['failed']} failed, {$result['app']} app notification(s).");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
