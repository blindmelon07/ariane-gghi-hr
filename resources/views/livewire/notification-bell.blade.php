<div wire:poll.30s x-data="{ open: false }" class="relative">
    {{-- Bell Button --}}
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-600 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>

        @if ($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.outside="open = false" x-transition
         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden" style="display: none;">

        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b">
            <span class="text-sm font-semibold text-gray-700">Notifications</span>
            @if ($this->unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Mark all read</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
            @forelse ($this->notifications as $notification)
                <div wire:click="markAsRead('{{ $notification->id }}')" class="px-4 py-3 text-sm cursor-pointer hover:bg-gray-50 {{ !$notification->read_at ? 'bg-indigo-50/50' : '' }}">
                    <p class="text-gray-700">{{ $notification->data['message'] ?? 'New notification' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">No notifications yet.</div>
            @endforelse
        </div>
    </div>
</div>
