<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds the HR text messages sent to employees and hands them to SmsService.
 */
class SmsNotificationService
{
    public function __construct(private SmsService $sms) {}

    /**
     * Tell each employee with a payslip in the period that it's ready.
     * Employees already notified for this period are skipped unless $resend is true.
     *
     * @return array{queued: int, failed: int, skipped: int, already: int}
     */
    public function payslipNotices(PayrollPeriod $period, bool $resend = false): array
    {
        $key      = "payslip:{$period->id}";
        $notified = $resend ? collect() : $this->alreadySent('payslip', $key);

        $payslips = Payslip::with('employee')
            ->where('payroll_period_id', $period->id)
            ->get()
            ->filter(fn ($p) => $p->employee && ! $notified->contains($p->employee_id));

        $range = $period->start_date->format('M d') . ' - ' . Carbon::parse($period->end_date)->format('M d, Y');

        $messages = $payslips->map(fn ($p) => [
            'number'      => $p->employee->cell_number,
            'message'     => "GGHI HR: Hi {$p->employee->first_name}, your payslip for {$period->name} ({$range}) is ready. Log in to the HR portal to view it.",
            'employee_id' => $p->employee_id,
            'type'        => 'payslip',
            'context_key' => $key,
        ])->values()->all();

        return $this->sms->sendMany($messages) + ['already' => $notified->count()];
    }

    public function overtimeDecision(OvertimeRequest $request): bool
    {
        $employee = $request->employee;
        if (! $employee?->cell_number) {
            return false;
        }

        $date = $request->date->format('M d, Y');
        $msg  = $request->status === 'approved'
            ? "GGHI HR: Hi {$employee->first_name}, your overtime request for {$date} has been APPROVED ({$this->hours($request->approved_hours)} hr)."
            : "GGHI HR: Hi {$employee->first_name}, your overtime request for {$date} has been REJECTED." . ($request->remarks ? " Reason: {$request->remarks}" : '');

        return $this->sms->send($employee->cell_number, $msg, [
            'employee_id' => $employee->id,
            'type'        => 'overtime',
            'context_key' => "overtime:{$request->id}",
        ]);
    }

    /**
     * Text employees who were late or absent on $date. Nursing staff work rotating
     * shifts, so they're excluded. Each employee gets at most one late and one
     * absent alert per date.
     *
     * @return array{queued: int, failed: int, skipped: int, late: int, absent: int}
     */
    public function attendanceAlerts(string $date): array
    {
        $day = Carbon::parse($date);

        $employees = Employee::where('is_active', true)
            ->whereNotNull('cell_number')->where('cell_number', '<>', '')
            ->get()
            ->filter(fn ($e) => strcasecmp(trim((string) $e->department), 'Nursing') !== 0)
            ->filter(fn ($e) => $this->isWorkingDay($e, $day));

        $sentLate   = $this->alreadySent('attendance', "late:{$day->toDateString()}");
        $sentAbsent = $this->alreadySent('attendance', "absent:{$day->toDateString()}");
        $processor  = app(AttendanceProcessorService::class);
        $label      = $day->format('M d, Y');
        $messages   = [];
        $counts     = ['late' => 0, 'absent' => 0];

        foreach ($employees as $e) {
            $result = $processor->processDay($e, $day->toDateString());

            if ($result['status'] === 'Late' && ! $sentLate->contains($e->id)) {
                $counts['late']++;
                $messages[] = [
                    'number'      => $e->cell_number,
                    'message'     => "GGHI HR: Hi {$e->first_name}, you were recorded LATE on {$label} by {$result['minutes_late']} minute(s). Time in: {$result['time_in']}. If this is wrong, please file a time correction.",
                    'employee_id' => $e->id,
                    'type'        => 'attendance',
                    'context_key' => "late:{$day->toDateString()}",
                ];
            } elseif ($result['status'] === 'Absent' && ! $sentAbsent->contains($e->id)) {
                $counts['absent']++;
                $messages[] = [
                    'number'      => $e->cell_number,
                    'message'     => "GGHI HR: Hi {$e->first_name}, no attendance was recorded for you on {$label}. If you were at work, please file a time correction.",
                    'employee_id' => $e->id,
                    'type'        => 'attendance',
                    'context_key' => "absent:{$day->toDateString()}",
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
        $loginUrl = rtrim(config('app.url'), '/') . '/login';
        $result   = ['sent' => [], 'failed' => []];

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
            $text     = "GGHI HR: Your HR portal login. Employee Code: {$emp->emp_code} Password: {$password} Log in at {$loginUrl} and change your password after signing in.";
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
     * Send one message to many employees. "{first_name}" and "{name}" are
     * replaced per employee.
     *
     * @param  Collection<int, Employee>  $employees
     * @return array{queued: int, failed: int, skipped: int}
     */
    public function broadcast(Collection $employees, string $message, string $type): array
    {
        $messages = $employees->map(fn ($e) => [
            'number'      => $e->cell_number,
            'message'     => str_replace(['{first_name}', '{name}'], [$e->first_name, $e->full_name], $message),
            'employee_id' => $e->id,
            'type'        => $type,
        ])->values()->all();

        return $this->sms->sendMany($messages);
    }

    /**
     * Whether $day is a scheduled working day: never Sunday, and not Saturday for
     * regular staff without a weekly rest day. Day-offs, holidays and leave are
     * handled by AttendanceProcessorService.
     */
    private function isWorkingDay(Employee $employee, Carbon $day): bool
    {
        if ($day->isSunday()) {
            return false;
        }

        $isProbationary = ($employee->employment_type ?? 'regular') === 'probationary';

        return ! (! $isProbationary && $employee->weekday_off === null && $day->isSaturday());
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
