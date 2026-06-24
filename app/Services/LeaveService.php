<?php

namespace App\Services;

use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\User;
use App\Notifications\LeaveRequestFiled;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class LeaveService
{
    // Role → step mapping for sequential approval
    public const APPROVAL_STEPS = [
        1 => ['role' => 'hr_admin',    'label' => 'HR'],
        2 => ['role' => 'approver',    'label' => 'Medical Director'],
        3 => ['role' => 'super_admin', 'label' => 'CEO'],
    ];

    public function getStepForUser(User $user): ?int
    {
        foreach (self::APPROVAL_STEPS as $step => $config) {
            if ($user->hasRole($config['role'])) {
                return $step;
            }
        }

        return null;
    }

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
     * Approve a leave request at the current step.
     * Advances to the next step or fully approves when the final step is reached.
     */
    public function approve(LeaveRequest $request, User $approver, ?string $remarks = null): void
    {
        $step   = $request->approval_step;
        $config = self::APPROVAL_STEPS[$step] ?? null;

        if (! $config) {
            return;
        }

        // Record this step's approval
        LeaveRequestApproval::updateOrCreate(
            ['leave_request_id' => $request->id, 'step' => $step],
            [
                'role'        => $config['role'],
                'label'       => $config['label'],
                'approver_id' => $approver->id,
                'action'      => 'approved',
                'remarks'     => $remarks,
                'acted_at'    => now(),
            ]
        );

        $nextStep = $step + 1;
        $isFinal  = ! isset(self::APPROVAL_STEPS[$nextStep]);

        if ($isFinal) {
            // All steps done — fully approve and deduct credits
            $request->update([
                'status'        => 'approved',
                'approval_step' => null,
                'approved_by'   => $approver->id,
                'approved_at'   => now(),
                'remarks'       => $remarks,
            ]);

            $credit = LeaveCredit::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $request->start_date->year)
                ->first();

            if ($credit) {
                $credit->update([
                    'used_credits' => $credit->used_credits + $request->total_days,
                ]);
            }

            $employee = $request->employee;
            $user = User::where('employee_code', $employee->emp_code)->first();
            $user?->notify(new LeaveStatusUpdated($request));

            ActivityLogService::log('leave_approved', "Final approval (CEO) for leave #{$request->id} — {$employee->full_name}", $request);
        } else {
            // Advance to next step
            $request->update(['approval_step' => $nextStep]);
            ActivityLogService::log(
                'leave_step_approved',
                "Step {$step} ({$config['label']}) approved leave #{$request->id} for {$request->employee->full_name}",
                $request
            );
        }
    }

    /**
     * Reject a leave request at any step — terminates the chain.
     */
    public function reject(LeaveRequest $request, User $approver, ?string $remarks = null): void
    {
        $step   = $request->approval_step ?? 1;
        $config = self::APPROVAL_STEPS[$step] ?? ['role' => $approver->role, 'label' => 'Approver'];

        LeaveRequestApproval::updateOrCreate(
            ['leave_request_id' => $request->id, 'step' => $step],
            [
                'role'        => $config['role'],
                'label'       => $config['label'],
                'approver_id' => $approver->id,
                'action'      => 'rejected',
                'remarks'     => $remarks,
                'acted_at'    => now(),
            ]
        );

        $request->update([
            'status'        => 'rejected',
            'approval_step' => null,
            'approved_by'   => $approver->id,
            'approved_at'   => now(),
            'remarks'       => $remarks,
        ]);

        $user = User::where('employee_code', $request->employee->emp_code)->first();
        $user?->notify(new LeaveStatusUpdated($request));

        ActivityLogService::log('leave_rejected', "Step {$step} ({$config['label']}) rejected leave #{$request->id} for {$request->employee->full_name}", $request);
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
