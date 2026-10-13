<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => 'Leave request ' . $this->leaveRequest->status, 'body' => $data['message'], 'url' => $data['url'], 'tag' => 'leave-' . $this->leaveRequest->id];
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
            'url'              => route('leave.my-requests'),
        ];
    }
}
