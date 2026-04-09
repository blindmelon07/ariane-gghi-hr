<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestFiled;
use App\Notifications\LeaveStatusUpdated;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class LeaveService
{
    /**
     * Compute working days between two dates (excludes Sundays only).
     */
    public function computeTotalDays(string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);
        $days  = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isSunday()) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Check if the employee has an overlapping approved/pending leave request.
     */
    public function hasOverlap(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        return LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Get remaining credits for an employee, leave type, and year.
     */
    public function getRemainingCredits(int $employeeId, int $leaveTypeId, int $year): float
    {
        $credit = LeaveCredit::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        return $credit ? (float) $credit->remaining_credits : 0;
    }

    /**
     * Approve a leave request.
     */
    public function approve(LeaveRequest $request, User $approver, ?string $remarks = null): void
    {
        $request->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'remarks'     => $remarks,
        ]);

        // Deduct credits
        $credit = LeaveCredit::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $request->start_date->year)
            ->first();

        if ($credit) {
            $credit->update([
                'used_credits' => $credit->used_credits + $request->total_days,
            ]);
        }

        // Notify the employee's linked user
        $user = User::where('employee_code', $request->employee->emp_code)->first();
        $user?->notify(new LeaveStatusUpdated($request));

        ActivityLogService::log('leave_approved', "Approved leave #{$request->id} for {$request->employee->full_name}", $request);
    }

    /**
     * Reject a leave request.
     */
    public function reject(LeaveRequest $request, User $approver, ?string $remarks = null): void
    {
        $request->update([
            'status'      => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'remarks'     => $remarks,
        ]);

        $user = User::where('employee_code', $request->employee->emp_code)->first();
        $user?->notify(new LeaveStatusUpdated($request));

        ActivityLogService::log('leave_rejected', "Rejected leave #{$request->id} for {$request->employee->full_name}", $request);
    }

    /**
     * Cancel a pending leave request.
     */
    public function cancel(LeaveRequest $request): void
    {
        if ($request->status !== 'pending') {
            return;
        }

        $request->update(['status' => 'cancelled']);
    }

    /**
     * Notify all HR admins about a new leave request.
     */
    public function notifyAdmins(LeaveRequest $request): void
    {
        $admins = User::where('role', 'hr_admin')->where('is_active', true)->get();
        Notification::send($admins, new LeaveRequestFiled($request));
    }
}
