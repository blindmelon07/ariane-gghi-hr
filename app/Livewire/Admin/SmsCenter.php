<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PushSubscription;
use App\Models\SmsLog;
use App\Models\User;
use App\Notifications\HrAlert;
use App\Services\ActivityLogService;
use App\Services\SmsNotificationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class SmsCenter extends Component
{
    use WithPagination;

    public string $tab = 'compose';

    // Compose (announcement / custom message)
    public string $audience            = 'all';   // all, department, employees, numbers
    public ?int   $departmentId        = null;
    public array  $selectedEmployeeIds = [];
    public string $customNumbers       = '';
    public string $smsMessage          = '';
    public string $employeeSearch      = '';
    public string $notifTitle          = '';      // app notification title (optional)
    public bool   $viaApp              = true;    // in-app bell + phone pop-up
    public bool   $viaSms              = true;    // text message (uses credits)

    // Login details
    public array  $credentialIds    = [];
    public string $credentialSearch = '';

    // Attendance alerts
    public string $alertDate = '';

    // History
    public string $filterType   = '';
    public string $filterStatus = '';

    public ?int $credits = null;

    public function mount(): void
    {
        $this->alertDate = now('Asia/Manila')->toDateString();   // app clock is UTC
        $this->credits   = app(SmsService::class)->credits();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function refreshCredits(): void
    {
        $this->credits = app(SmsService::class)->credits();
    }

    #[Computed]
    public function configured(): bool
    {
        return app(SmsService::class)->isConfigured();
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return $this->activeEmployees($this->employeeSearch);
    }

    #[Computed]
    public function credentialEmployees()
    {
        return $this->activeEmployees($this->credentialSearch);
    }

    #[Computed]
    public function recipients()
    {
        return match ($this->audience) {
            'all'        => Employee::where('is_active', true)->get(),
            'department' => $this->departmentId
                ? Employee::where('is_active', true)->where('department_id', $this->departmentId)->get()
                : collect(),
            'employees'  => Employee::whereIn('id', $this->selectedEmployeeIds)->get(),
            default      => collect(),
        };
    }

    #[Computed]
    public function numberList(): array
    {
        return collect(preg_split('/[\s,;]+/', $this->customNumbers))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    #[Computed]
    public function logs()
    {
        return SmsLog::with('employee')
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest('id')
            ->paginate(20);
    }

    public function send(SmsNotificationService $notifier, SmsService $sms): void
    {
        // Typed numbers can only be texted.
        $viaApp = $this->viaApp && $this->audience !== 'numbers';
        $viaSms = $this->viaSms || $this->audience === 'numbers';

        if (! $viaApp && ! $viaSms) {
            $this->addError('viaApp', 'Choose at least one: App notification or SMS.');
            return;
        }

        // UniSMS rejects texts under 10 characters; app notifications have no minimum.
        $this->validate([
            'smsMessage'   => 'required|string|max:' . SmsService::MAX_LENGTH . ($viaSms ? '|min:' . SmsService::MIN_LENGTH : ''),
            'notifTitle'   => 'nullable|string|max:80',
            'audience'     => 'required|in:all,department,employees,numbers',
            'departmentId' => 'required_if:audience,department',
        ], [
            'departmentId.required_if' => 'Choose a department.',
            'smsMessage.required'      => 'Type a message.',
            'smsMessage.min'           => 'The message must be at least ' . SmsService::MIN_LENGTH . ' characters (UniSMS rejects shorter texts).',
        ]);

        // The sender ID rejects texts that contain links (app notifications are fine).
        if ($viaSms && preg_match('~https?://|www\.|\b[a-z0-9-]+\.(com|ph|net|org)\b~i', $this->smsMessage)) {
            $this->addError('smsMessage', 'Links are not allowed in SMS by the sender ID — remove the web address, or untick SMS to send it as an app notification only.');
            return;
        }

        if ($this->audience === 'numbers') {
            if (! $this->numberList) {
                $this->addError('customNumbers', 'Enter at least one mobile number.');
                return;
            }
            $result = $sms->sendMany(array_map(fn ($n) => [
                'number'  => $n,
                'message' => $this->smsMessage,
                'type'    => 'custom',
            ], $this->numberList));
        } else {
            if ($this->recipients->isEmpty()) {
                $this->addError('audience', 'No employees selected.');
                return;
            }
            $type   = in_array($this->audience, ['all', 'department'], true) ? 'announcement' : 'custom';
            $result = $notifier->broadcast($this->recipients, $this->smsMessage, $type, $this->notifTitle, $viaApp, $viaSms);
        }

        $channels = implode(' + ', array_keys(array_filter(['app' => $viaApp, 'SMS' => $viaSms])));
        ActivityLogService::log('sms_sent', "Sent {$channels} message to {$this->audience}: {$result['queued']} SMS queued, " . ($result['app'] ?? 0) . " app, {$result['skipped']} skipped, {$result['failed']} failed");

        $this->reset('smsMessage', 'notifTitle', 'selectedEmployeeIds', 'customNumbers');
        if ($viaSms) {
            $this->credits = $sms->credits();
        }
        unset($this->logs);
        $this->flashResult($result, $viaSms);
    }

    public function sendCredentials(SmsNotificationService $notifier): void
    {
        if (! $this->credentialIds) {
            $this->addError('credentialIds', 'Select at least one employee.');
            return;
        }

        $employees = Employee::with('user')->whereIn('id', $this->credentialIds)->get();

        // Same rule as User Accounts: only a super admin can reset a super admin's password.
        $blocked = Auth::user()?->role === 'super_admin'
            ? collect()
            : $employees->filter(fn ($e) => ($e->user ?? User::where('employee_code', $e->emp_code)->first())?->role === 'super_admin');

        $result = $notifier->sendCredentials($employees->diff($blocked));
        foreach ($blocked as $e) {
            $result['failed'][] = "{$e->full_name} (super admin — only a super admin can reset this)";
        }

        $this->credentialIds = [];
        $this->credits       = app(SmsService::class)->credits();
        unset($this->logs, $this->credentialEmployees);

        $msg = count($result['sent']) . ' employee(s) texted their login details.';
        if ($result['failed']) {
            session()->flash('error', 'Not sent: ' . implode('; ', $result['failed']) . '.');
        }
        session()->flash('success', $msg);
    }

    public function runAttendanceAlerts(SmsNotificationService $notifier): void
    {
        $this->validate(['alertDate' => 'required|date|before_or_equal:' . now('Asia/Manila')->toDateString()]);

        $result = $notifier->attendanceAlerts($this->alertDate);
        ActivityLogService::log('sms_attendance_alerts', "Attendance alerts for {$this->alertDate}: {$result['late']} late, {$result['absent']} absent");

        $this->credits = app(SmsService::class)->credits();
        unset($this->logs);
        session()->flash('success', "{$this->alertDate}: {$result['late']} late, {$result['absent']} absent — {$result['queued']} sent, {$result['skipped']} skipped, {$result['failed']} failed, {$result['app']} app notification(s). Employees already alerted for this date were not alerted again.");
    }

    /** Devices (phones/computers) that turned on notifications, newest first. */
    #[Computed]
    public function pushDevices()
    {
        return PushSubscription::with('user')->latest('updated_at')->get();
    }

    /**
     * Send a test notification to every user who has at least one device
     * registered. App only — no SMS, no credits.
     */
    public function sendTestToAllDevices(): void
    {
        $users = User::whereHas('pushSubscriptions')->get();

        if ($users->isEmpty()) {
            session()->flash('error', 'No devices have notifications turned on yet. Open the portal on a phone, tap the bell → Turn on phone notifications.');
            return;
        }

        Notification::send($users, new HrAlert(
            'Test notification',
            'This is a test from HR. If you can see this, notifications work on your device.',
            null,
            'test'
        ));

        ActivityLogService::log('push_test_all', "Sent a test notification to {$users->count()} user(s) on {$this->pushDevices->count()} device(s)");
        unset($this->pushDevices);

        session()->flash('success', "Test sent to {$users->count()} user(s) on {$this->pushDevices->count()} device(s). Devices that were uninstalled or blocked are removed from the list automatically.");
    }

    public function toggleAllCredentials(): void
    {
        $visible = $this->credentialEmployees->pluck('id')->all();
        $this->credentialIds = array_diff($visible, $this->credentialIds) ? array_values(array_unique([...$this->credentialIds, ...$visible])) : [];
    }

    private function activeEmployees(string $search)
    {
        return Employee::with(['user', 'dept'])
            ->where('is_active', true)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('emp_code', 'like', "%{$search}%")))
            ->orderBy('last_name')
            ->get();
    }

    private function flashResult(array $result, bool $viaSms = true): void
    {
        $parts = [];
        if (isset($result['app'])) {
            $parts[] = "{$result['app']} app notification(s) sent"
                . ($result['app'] === 0 ? ' (no recipient has a login account)' : '');
        }
        if ($viaSms) {
            $sms = "{$result['queued']} SMS sent";
            if ($result['skipped']) {
                $sms .= ", {$result['skipped']} skipped (missing or invalid number)";
            }
            if ($result['failed']) {
                $sms .= ", {$result['failed']} failed — see History";
            }
            $parts[] = $sms;
        }

        session()->flash($result['queued'] || ! empty($result['app']) ? 'success' : 'error', implode('. ', $parts) . '.');
    }

    public function render()
    {
        return view('livewire.admin.sms-center');
    }
}
