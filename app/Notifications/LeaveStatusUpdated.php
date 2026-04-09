<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'leave_request_id' => $this->leaveRequest->id,
            'leave_type'       => $this->leaveRequest->leaveType->name,
            'status'           => $this->leaveRequest->status,
            'start_date'       => $this->leaveRequest->start_date->toDateString(),
            'end_date'         => $this->leaveRequest->end_date->toDateString(),
            'message'          => 'Your ' . $this->leaveRequest->leaveType->name . ' request has been ' . $this->leaveRequest->status . '.',
        ];
    }
}
