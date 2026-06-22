<?php

namespace App\Livewire\Admin;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\ZKTecoService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BiometricsManager extends Component
{
    public string $fromDate = '';
    public string $toDate   = '';

    public ?array $connectionStatus = null;

    public bool $testing      = false;
    public bool $syncing      = false;
    public bool $syncingUsers = false;

    public string $successMessage = '';
    public string $errorMessage   = '';

    // Device account modal
    public bool   $showAccountModal   = false;
    public ?int   $accountEmployeeId  = null;
    public string $accountName        = '';
    public string $accountEmpCode     = '';
    public string $accountPassword    = '';
    public string $accountRole        = 'employee';
    public bool   $hasExistingAccount = false;

    public function mount(): void
    {
        $this->fromDate = now()->toDateString();
        $this->toDate   = now()->toDateString();
    }

    public function testConnection(): void
    {
        $this->testing        = true;
        $this->successMessage = '';
        $this->errorMessage   = '';
        $this->connectionStatus = null;

        try {
            $this->connectionStatus = app(ZKTecoService::class)->testConnection();
            if ($this->connectionStatus['connected']) {
                $this->successMessage = 'Device connected successfully.';
            } else {
                $this->errorMessage = $this->connectionStatus['message'] ?? 'Connection failed.';
            }
        } catch (\Throwable $e) {
            $this->connectionStatus = ['connected' => false, 'message' => $e->getMessage()];
            $this->errorMessage     = 'Connection failed: ' . $e->getMessage();
        } finally {
            $this->testing = false;
        }
    }

    public function syncAttendance(): void
    {
        $this->validate([
            'fromDate' => 'required|date',
            'toDate'   => 'required|date|after_or_equal:fromDate',
        ]);

        $this->syncing        = true;
        $this->successMessage = '';
        $this->errorMessage   = '';

        try {
            Artisan::call('sync:attendance', [
                '--from' => $this->fromDate,
                '--to'   => $this->toDate,
            ]);
            $this->successMessage = "Attendance synced ({$this->fromDate} to {$this->toDate}). Check Sync History below.";
            unset($this->recentSyncLogs);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Sync failed: ' . $e->getMessage();
        } finally {
            $this->syncing = false;
        }
    }

    public function syncUsers(): void
    {
        $this->syncingUsers   = true;
        $this->successMessage = '';
        $this->errorMessage   = '';

        try {
            Artisan::call('sync:employees');
            $this->successMessage = 'Employees synced from device successfully.';
            unset($this->recentSyncLogs);
            unset($this->deviceUsers);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Sync failed: ' . $e->getMessage();
        } finally {
            $this->syncingUsers = false;
        }
    }

    #[Computed]
    public function deviceUsers()
    {
        return Employee::with('user')
            ->whereNotNull('synced_at')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function recentSyncLogs()
    {
        return SyncLog::orderByDesc('started_at')->limit(15)->get();
    }

    public function openAccountModal(int $id): void
    {
        $emp = Employee::with('user')->findOrFail($id);

        $this->accountEmployeeId  = $emp->id;
        $this->accountName        = $emp->full_name;
        $this->accountEmpCode     = $emp->emp_code;
        $this->accountPassword    = '';
        $this->hasExistingAccount = (bool) $emp->user;
        $this->accountRole        = $emp->user?->role ?? 'employee';
        $this->showAccountModal   = true;
    }

    public function saveAccount(): void
    {
        $this->validate([
            'accountPassword' => $this->hasExistingAccount ? 'nullable|string|min:6' : 'required|string|min:6',
            'accountRole'     => 'required|in:employee,hr_admin,manager',
        ]);

        $emp = Employee::findOrFail($this->accountEmployeeId);

        if ($this->hasExistingAccount && $emp->user) {
            $emp->user->update(['role' => $this->accountRole]);
            if ($this->accountPassword) {
                $emp->user->update(['password' => Hash::make($this->accountPassword)]);
            }
            $this->successMessage = "Account updated for {$emp->full_name}.";
        } else {
            $existing = User::where('employee_code', $emp->emp_code)->first();

            if ($existing) {
                $emp->update(['user_id' => $existing->id]);
                $existing->update(['role' => $this->accountRole, 'is_active' => true]);
            } else {
                $user = User::create([
                    'name'          => $emp->full_name,
                    'employee_code' => $emp->emp_code,
                    'password'      => Hash::make($this->accountPassword),
                    'role'          => $this->accountRole,
                    'is_active'     => true,
                ]);
                $emp->update(['user_id' => $user->id]);
            }

            $this->successMessage = "Account created for {$emp->full_name}.";
        }

        $this->showAccountModal = false;
        unset($this->deviceUsers);
    }

    public function closeAccountModal(): void
    {
        $this->showAccountModal = false;
    }

    public function getRecentLogsProperty()
    {
        return AttendanceLog::with('employee')
            ->orderByDesc('punch_time')
            ->limit(15)
            ->get();
    }

    public function getTodayCountProperty(): int
    {
        return AttendanceLog::whereDate('punch_date', now()->toDateString())->count();
    }

    public function getTotalLogsProperty(): int
    {
        return AttendanceLog::count();
    }

    public function render()
    {
        return view('livewire.admin.biometrics-manager');
    }
}
