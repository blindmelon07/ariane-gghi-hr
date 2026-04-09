<?php

namespace App\Livewire\Admin;

use App\Jobs\SyncEmployeesJob;
use App\Models\ActivityLog;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    #[Computed(cache: true, seconds: 300)]
    public function totalActiveEmployees(): int
    {
        return Employee::where('is_active', true)->count();
    }

    #[Computed(cache: true, seconds: 300)]
    public function presentToday(): int
    {
        return AttendanceLog::whereDate('punch_date', today())
            ->where('punch_state', 0)
            ->distinct('emp_code')
            ->count('emp_code');
    }

    #[Computed(cache: true, seconds: 300)]
    public function absentToday(): int
    {
        if (today()->isSunday()) {
            return 0;
        }

        return max(0, $this->totalActiveEmployees - $this->presentToday);
    }

    #[Computed(cache: true, seconds: 300)]
    public function lateToday(): int
    {
        return AttendanceLog::whereDate('punch_date', today())
            ->where('punch_state', 0)
            ->whereTime('punch_time', '>', '08:00:00')
            ->distinct('emp_code')
            ->count('emp_code');
    }

    #[Computed(cache: true, seconds: 300)]
    public function pendingLeaves(): int
    {
        return LeaveRequest::where('status', 'pending')->count();
    }

    #[Computed(cache: true, seconds: 300)]
    public function pendingPayroll(): int
    {
        return PayrollPeriod::whereIn('status', ['draft', 'processing'])->count();
    }

    #[Computed]
    public function recentActivity()
    {
        return ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function todayBirthdays()
    {
        return Employee::where('is_active', true)
            ->whereMonth('date_of_birth', today()->month)
            ->whereDay('date_of_birth', today()->day)
            ->get();
    }

    public function syncBioTime(): void
    {
        SyncEmployeesJob::dispatch();
        $this->dispatch('toast', message: 'BioTime sync dispatched.');
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
