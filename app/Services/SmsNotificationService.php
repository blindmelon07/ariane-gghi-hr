<?php

namespace App\Services;

use App\Models\DayOffSwap;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SmsLog;
use App\Models\User;
use App\Notifications\HrAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds the HR messages sent to employees: a text through SmsService and an
 * in-app/phone notification (HrAlert) for the same event.
 */
class SmsNotificationService
{
    public function __construct(private SmsService $sms) {}

    /**
     * Tell each employee with a payslip in the period that it's ready, by text
     * and in the app. Employees already notified for this period are skipped
     * unless $resend is true.
     *
     * @return array{queued: int, failed: int, skipped: int, already: int, app: int}
     */
    public function payslipNotices(PayrollPeriod $period, bool $resend = false): array
    {
        $key      = "payslip:{$period->id}";
        $notified = $resend ? collect() : $this->alreadySent('payslip', $key);
        $range    = $period->start_date->format('M d') . ' - ' . Carbon::parse($period->end_date)->format('M d, Y');

        $payslips = Payslip::with('employee.user')
            ->where('payroll_period_id', $period->id)
            ->get()
            ->filter(fn ($p) => $p->employee);

        $app = 0;
        foreach ($payslips as $p) {
            $app += (int) $this->notifyApp($p->employee, 'Payslip ready',
                "Your payslip for {$period->name} ({$range}) is ready.",
                route('payslips.index'), 'payslip', $resend ? null : $key);
        }

        $messages = $payslips->reject(fn ($p) => $notified->contains($p->employee_id))->map(fn ($p) => [
            'number'      => $p->employee->cell_number,
            'message'     => "GGHI HR: Hi {$p->employee->first_name}, your payslip for {$period->name} ({$range}) is ready. Log in to the HR portal to view it.",
            'employee_id' => $p->employee_id,
            'type'        => 'payslip',
            'context_key' => $key,
        ])->values()->all();

        return $this->sms->sendMany($messages) + ['already' => $notified->count(), 'app' => $app];
    }

    public function overtimeDecision(OvertimeRequest $request): bool
    {
        $employee = $request->employee;
        if (! $employee) {
            return false;
        }

        $date     = $request->date->format('M d, Y');
        $approved = $request->status === 'approved';
        $detail   = $approved
            ? "has been APPROVED ({$this->hours($request->approved_hours)} hr)."
            : 'has been REJECTED.' . ($request->remarks ? " Reason: {$request->remarks}" : '');

        $this->notifyApp($employee, $approved ? 'Overtime approved' : 'Overtime rejected',
            "Your overtime request for {$date} {$detail}", route('overtime.request'), 'overtime', "overtime:{$request->id}");

        if (! $employee->cell_number) {
            return false;
        }

        return $this->sms->send($employee->cell_number, "GGHI HR: Hi {$employee->first_name}, your overtime request for {$date} {$detail}", [
            'employee_id' => $employee->id,
            'type'        => 'overtime',
            'context_key' => "overtime:{$request->id}",
        ]);
    }

    /**
     * Final decision on any request (time correction, trip ticket, …): bell +
     * phone notification, plus an SMS. $what reads like "time correction for Oct 05, 2026".
     */
    public function requestDecision(Employee $employee, string $what, bool $approved, ?string $remarks, string $url, string $contextKey): void
    {
        $status = $approved ? 'APPROVED' : 'REJECTED';
        $reason = ! $approved && $remarks ? " Reason: {$remarks}" : '';
        $body   = "Your {$what} has been {$status}.{$reason}";

        $this->notifyApp($employee, ucfirst($what) . ($approved ? ' — approved' : ' — rejected'), $body, $url, 'request', "{$contextKey}:decision");

        if ($employee->cell_number) {
            $this->sms->send($employee->cell_number, "GGHI HR: Hi {$employee->first_name}, your {$what} has been {$status}.{$reason}", [
                'employee_id' => $employee->id,
                'type'        => 'request',
                'context_key' => "{$contextKey}:decision",
            ]);
        }
    }

    /**
     * A multi-step request moved to the next approver: app notification only
     * (no SMS), so the employee can follow it without spending credits.
     */
    public function requestProgress(Employee $employee, string $what, string $approvedBy, string $nextApprover, string $url, string $contextKey): void
    {
        $this->notifyApp($employee, ucfirst($what) . ' — in progress',
            "Your {$what} was approved by {$approvedBy} and is now waiting for {$nextApprover}.",
            $url, 'request', "{$contextKey}:step:{$approvedBy}");
    }

    /**
     * Alert employees who were late or absent on $date, by text and in the app.
     * Nursing staff work rotating shifts, so they're excluded. Each employee gets
     * at most one late and one absent alert per date.
     *
     * @return array{queued: int, failed: int, skipped: int, late: int, absent: int, app: int}
     */
    public function attendanceAlerts(string $date): array
    {
        $day   = Carbon::parse($date);
        $ymd   = $day->toDateString();
        $label = $day->format('M d, Y');

        $employees = Employee::with('user')->where('is_active', true)
            ->get()
            ->filter(fn ($e) => strcasecmp(trim((string) $e->department), 'Nursing') !== 0)
            ->filter(fn ($e) => $this->isWorkingDay($e, $day));

        $sentLate   = $this->alreadySent('attendance', "late:{$ymd}");
        $sentAbsent = $this->alreadySent('attendance', "absent:{$ymd}");
        $processor  = app(AttendanceProcessorService::class);
        $messages   = [];
        $counts     = ['late' => 0, 'absent' => 0, 'app' => 0];

        foreach ($employees as $e) {
            $result = $processor->processDay($e, $ymd);

            if ($result['status'] === 'Late') {
                $kind = 'late';
                $text = "You were recorded LATE on {$label} by {$result['minutes_late']} minute(s). Time in: {$result['time_in']}. If this is wrong, please file a time correction.";
                $sent = $sentLate;
            } elseif ($result['status'] === 'Absent') {
                $kind = 'absent';
                $text = "No attendance was recorded for you on {$label}. If you were at work, please file a time correction.";
                $sent = $sentAbsent;
            } else {
                continue;
            }

            $newInApp = $this->notifyApp($e, $kind === 'late' ? 'Late arrival' : 'No attendance recorded',
                $text, route('time-correction.request'), 'attendance', "{$kind}:{$ymd}");
            $newSms = ! $sent->contains($e->id) && SmsService::normalize($e->cell_number);

            if ($newInApp || $newSms) {
                $counts[$kind]++;
            }
            $counts['app'] += (int) $newInApp;

            if ($newSms) {
                $messages[] = [
                    'number'      => $e->cell_number,
                    'message'     => "GGHI HR: Hi {$e->first_name}, " . lcfirst($text),
                    'employee_id' => $e->id,
                    'type'        => 'attendance',
                    'context_key' => "{$kind}:{$ymd}",
                ];
            }
        }

        return $this->sms->sendMany($messages) + $counts;
    }

    /**
     * Give each employee a new random password (creating an employee account if
     * they have none) and text them their login. The password is only changed
     * when the text is accepted, so a failed send never locks anyone out.
     *
     * @param  Collection<int, Employee>  $employees
     * @return array{sent: array<int, string>, failed: array<int, string>}
     */
    public function sendCredentials(Collection $employees): array
    {
        $result = ['sent' => [], 'failed' => []];

        foreach ($employees as $emp) {
            if (! SmsService::normalize($emp->cell_number)) {
                $result['failed'][] = "{$emp->full_name} (no valid mobile number)";
                continue;
            }

            $user = $emp->user ?? User::where('employee_code', $emp->emp_code)->first();
            if ($user && ! $user->is_active) {
                $result['failed'][] = "{$emp->full_name} (account is deactivated)";
                continue;
            }

            $password = Str::password(8, symbols: false);
            // No URL: the sender ID rejects messages containing links.
            $text     = "GGHI HR: Your HR portal login. Employee Code: {$emp->emp_code} Password: {$password} Please log in to the GGHI HR portal and change your password after signing in.";
            $context  = [
                'employee_id' => $emp->id,
                'type'        => 'credentials',
                'log_content' => str_replace($password, '********', $text),
            ];

            if (! $user) {
                $user = User::create([
                    'name'          => $emp->full_name,
                    'employee_code' => $emp->emp_code,
                    'password'      => Hash::make(Str::random(40)),
                    'role'          => 'employee',
                    'is_active'     => true,
                ]);
                $user->syncRoles('employee');
            }
            if ($emp->user_id !== $user->id) {
                $emp->update(['user_id' => $user->id]);
            }

            if ($this->sms->send($emp->cell_number, $text, $context)) {
                $user->update(['password' => Hash::make($password)]);
                ActivityLogService::log('credentials_sms', "Texted login details to {$emp->full_name} ({$emp->emp_code})", $emp);
                $result['sent'][] = $emp->full_name;
            } else {
                $result['failed'][] = "{$emp->full_name} (SMS not accepted)";
            }
        }

        return $result;
    }

    /**
     * Send one message to many employees, in the app and/or by text. "{first_name}"
     * and "{name}" are replaced per employee in both the title and the message.
     *
     * @param  Collection<int, Employee>  $employees
     * @param  ?string  $title  app notification title; defaults to "Announcement" / "Message from HR"
     * @return array{queued: int, failed: int, skipped: int, app: int}
     */
    public function broadcast(Collection $employees, string $message, string $type, ?string $title = null, bool $viaApp = true, bool $viaSms = true): array
    {
        $personal = fn ($e, string $text) => str_replace(['{first_name}', '{name}'], [$e->first_name, $e->full_name], $text);
        $title    = trim((string) $title) ?: ($type === 'announcement' ? 'Announcement' : 'Message from HR');

        $app = 0;
        if ($viaApp) {
            foreach ($employees as $e) {
                $app += (int) $this->notifyApp($e, $personal($e, $title), $personal($e, $message), null, $type);
            }
        }

        if (! $viaSms) {
            return ['queued' => 0, 'failed' => 0, 'skipped' => 0, 'app' => $app];
        }

        $messages = $employees->map(fn ($e) => [
            'number'      => $e->cell_number,
            'message'     => $personal($e, $message),
            'employee_id' => $e->id,
            'type'        => $type,
        ])->values()->all();

        return $this->sms->sendMany($messages) + ['app' => $app];
    }

    /**
     * Whether $day is a scheduled working day: never Sunday, and not Saturday for
     * regular staff without a weekly rest day — unless the employee swapped that
     * rest day away. Day-offs, holidays and leave are handled by
     * AttendanceProcessorService.
     */
    private function isWorkingDay(Employee $employee, Carbon $day): bool
    {
        $isProbationary = ($employee->employment_type ?? 'regular') === 'probationary';
        $isRestDay      = $day->isSunday() || (! $isProbationary && $employee->weekday_off === null && $day->isSaturday());

        return ! $isRestDay || DayOffSwap::isSwappedWorkday($employee->id, $day);
    }

    /**
     * Put a notification in the employee's bell and push it to their devices.
     * With a $contextKey, an employee is never notified twice for the same thing.
     * Returns false when the employee has no login account or was already notified.
     */
    private function notifyApp(Employee $employee, string $title, string $body, ?string $url, string $type, ?string $contextKey = null): bool
    {
        $user = $employee->user ?? User::where('employee_code', $employee->emp_code)->first();
        if (! $user) {
            return false;
        }

        if ($contextKey && $user->notifications()->where('data->context_key', $contextKey)->exists()) {
            return false;
        }

        $user->notify(new HrAlert($title, $body, $url, $type, $contextKey));

        return true;
    }

    /** @return Collection<int, int> employee ids already sent a queued message with this key */
    private function alreadySent(string $type, string $key): Collection
    {
        return SmsLog::where('type', $type)
            ->where('context_key', $key)
            ->where('status', 'queued')
            ->pluck('employee_id');
    }

    private function hours($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    }
}
