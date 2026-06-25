<?php

namespace App\Services;

use App\Models\TripTicket;
use App\Models\TripTicketApproval;
use App\Models\User;

class TripTicketService
{
    // Step 1: Immediate Head approves the trip request
    // Step 2: HR Officer certifies driver fitness
    // Step 3: Fleet (HR/Super Admin) schedules or denies the vehicle
    public const APPROVAL_STEPS = [
        1 => ['role' => ['manager', 'department_head'], 'label' => 'Immediate Head'],
        2 => ['role' => 'hr_admin',                     'label' => 'HR Officer'],
        3 => ['role' => ['hr_admin', 'super_admin'],    'label' => 'Fleet'],
    ];

    /**
     * Returns all approval steps applicable to the given user.
     * hr_admin appears in both step 2 and step 3.
     */
    public function getApplicableSteps(User $user): array
    {
        $steps = [];
        foreach (self::APPROVAL_STEPS as $step => $config) {
            foreach ((array) $config['role'] as $role) {
                if ($user->role === $role) {
                    $steps[] = $step;
                    break;
                }
            }
        }
        return $steps;
    }

    /**
     * Determine the initial step when a trip ticket is filed.
     * HR admins and super admins skip to step 3 (fleet only).
     * Managers/department heads skip to step 2 (HR certification).
     */
    public function getInitialStep(User $filer): int
    {
        return match ($filer->role) {
            'super_admin'    => 3,
            'hr_admin'       => 3,
            'manager',
            'department_head' => 2,
            default          => 1,
        };
    }

    public function approve(TripTicket $ticket, User $approver, ?string $remarks = null): void
    {
        $step   = $ticket->approval_step;
        $config = self::APPROVAL_STEPS[$step] ?? null;

        if (! $config) {
            return;
        }

        TripTicketApproval::updateOrCreate(
            ['trip_ticket_id' => $ticket->id, 'step' => $step],
            [
                'role'        => $approver->role,
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
            $ticket->update([
                'status'        => 'approved',
                'approval_step' => null,
                'approved_by'   => $approver->id,
                'approved_at'   => now(),
                'remarks'       => $remarks,
            ]);

            // Mark the assigned vehicle as in-use
            if ($ticket->vehicle_id) {
                $ticket->vehicle->update(['status' => 'in_use']);
            }

            ActivityLogService::log(
                'trip_ticket_approved',
                "Trip ticket #{$ticket->id} fully approved (Fleet) for {$ticket->employee->full_name}",
                $ticket
            );
        } else {
            $ticket->update(['approval_step' => $nextStep]);

            ActivityLogService::log(
                'trip_ticket_step_approved',
                "Trip ticket #{$ticket->id} step {$step} ({$config['label']}) approved for {$ticket->employee->full_name}",
                $ticket
            );
        }
    }

    public function reject(TripTicket $ticket, User $approver, ?string $remarks = null): void
    {
        $step   = $ticket->approval_step ?? 1;
        $config = self::APPROVAL_STEPS[$step] ?? ['label' => 'Approver'];

        TripTicketApproval::updateOrCreate(
            ['trip_ticket_id' => $ticket->id, 'step' => $step],
            [
                'role'        => $approver->role,
                'label'       => $config['label'],
                'approver_id' => $approver->id,
                'action'      => 'rejected',
                'remarks'     => $remarks,
                'acted_at'    => now(),
            ]
        );

        $ticket->update([
            'status'        => 'rejected',
            'approval_step' => null,
            'approved_by'   => $approver->id,
            'approved_at'   => now(),
            'remarks'       => $remarks,
        ]);

        ActivityLogService::log(
            'trip_ticket_rejected',
            "Trip ticket #{$ticket->id} rejected at step {$step} ({$config['label']}) for {$ticket->employee->full_name}",
            $ticket
        );
    }

    public function depart(TripTicket $ticket, User $user): void
    {
        if ($ticket->status !== 'approved' || $ticket->departed_at !== null) {
            return;
        }

        $ticket->update([
            'departed_at' => now(),
            'departed_by' => $user->id,
        ]);

        ActivityLogService::log(
            'trip_ticket_departed',
            "Trip ticket #{$ticket->id} — vehicle departed, confirmed by {$user->name}",
            $ticket
        );
    }

    public function complete(TripTicket $ticket, User $user, ?string $returnRemarks = null): void
    {
        if ($ticket->status !== 'approved') {
            return;
        }

        $ticket->update([
            'status'         => 'completed',
            'completed_at'   => now(),
            'completed_by'   => $user->id,
            'return_remarks' => $returnRemarks,
        ]);

        // Release the vehicle back to available
        if ($ticket->vehicle_id) {
            $ticket->vehicle->update(['status' => 'available']);
        }

        ActivityLogService::log(
            'trip_ticket_completed',
            "Trip ticket #{$ticket->id} marked returned by {$user->name}. Vehicle released.",
            $ticket
        );
    }

    public function cancel(TripTicket $ticket): void
    {
        if ($ticket->status !== 'pending') {
            return;
        }
        $ticket->update(['status' => 'cancelled']);
    }
}
