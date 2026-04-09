<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestFiled extends Notification
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
            'employee_name'    => $this->leaveRequest->employee->full_name,
            'leave_type'       => $this->leaveRequest->leaveType->name,
            'start_date'       => $this->leaveRequest->start_date->toDateString(),
            'end_date'         => $this->leaveRequest->end_date->toDateString(),
            'total_days'       => $this->leaveRequest->total_days,
            'message'          => $this->leaveRequest->employee->full_name . ' filed a ' . $this->leaveRequest->leaveType->name . ' request.',
        ];
    }
}
