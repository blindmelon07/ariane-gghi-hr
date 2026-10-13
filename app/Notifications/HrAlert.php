<?php

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use Illuminate\Notifications\Notification;

/**
 * General-purpose employee notification: shows in the bell and pops up on
 * every device the employee enabled notifications on.
 *
 * $type is one of payslip, overtime, announcement, attendance;
 * $contextKey lets callers avoid notifying the same thing twice.
 */
class HrAlert extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $type = 'announcement',
        public ?string $contextKey = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'       => $this->title,
            'message'     => $this->body,
            'url'         => $this->url,
            'type'        => $this->type,
            'context_key' => $this->contextKey,
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url ?? url('/dashboard'),
            'tag'   => $this->contextKey ?? $this->type,
        ];
    }
}
