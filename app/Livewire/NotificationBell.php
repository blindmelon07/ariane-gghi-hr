<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
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

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->notifications);
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        $notification?->markAsRead();
        unset($this->unreadCount, $this->notifications);
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
