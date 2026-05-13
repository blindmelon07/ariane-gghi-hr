<?php

namespace App\Livewire\Admin;

use App\Models\AttendanceLog;
use App\Services\ZKTecoService;
use Livewire\Component;
use Illuminate\Support\Carbon;

class BiometricsManager extends Component
{
    public string $fromDate = '';
    public string $toDate   = '';

    public ?array $connectionStatus = null;
    public ?array $syncResult       = null;
    public ?array $userSyncResult   = null;

    public bool $testing      = false;
    public bool $syncing      = false;
    public bool $syncingUsers = false;

    public string $successMessage = '';
    public string $errorMessage   = '';

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
                $this->errorMessage = $this->connectionStatus['message'];
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
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
        $this->syncResult     = null;

        try {
            $this->syncResult     = app(ZKTecoService::class)->syncAttendance($this->fromDate, $this->toDate);
            $this->successMessage = "Synced {$this->syncResult['synced']} attendance records.";
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
        $this->userSyncResult = null;

        try {
            $this->userSyncResult = app(ZKTecoService::class)->syncUsers();
            $this->successMessage = "Synced {$this->userSyncResult['synced']} employees from device.";
        } catch (\Throwable $e) {
            $this->errorMessage = 'User sync failed: ' . $e->getMessage();
        } finally {
            $this->syncingUsers = false;
        }
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
