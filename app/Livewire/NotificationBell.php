<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->take(5)->get();
    }

    /**
     * Re-read the badge and list. Fired instantly when a push arrives on this
     * device (see layouts/_push-notifications), and by the 60s poll as a fallback
     * for devices without phone notifications.
     */
    #[On('notification-received')]
    public function refreshNotifications(): void
    {
        unset($this->unreadCount, $this->notifications);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->notifications);
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if (! $notification) {
            return;
        }

        $notification->markAsRead();
        unset($this->unreadCount, $this->notifications);

        $url = $notification->data['url'] ?? $this->resolveUrl($notification);
        if ($url) {
            $this->redirect($url, navigate: true);
        }
    }

    private function resolveUrl(object $notification): ?string
    {
        $role = auth()->user()?->role;
        $type = class_basename($notification->type);

        return match ($type) {
            'LeaveRequestFiled'  => in_array($role, ['hr_admin', 'approver', 'super_admin', 'manager', 'department_head'])
                                        ? route('admin.leave')
                                        : null,
            'LeaveStatusUpdated' => route('leave.my-requests'),
            default              => null,
        };
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
