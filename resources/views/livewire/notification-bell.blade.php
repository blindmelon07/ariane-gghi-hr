<div x-data="{
        open: false,
        panelStyle: {},
        toggle() {
            // On phones the bell isn't at the screen edge, so a right-aligned 20rem panel
            // runs off the left side. Pin it full-width just below the bell instead.
            // (Inline style, so no new Tailwind classes need compiling. An object, not a
            // string, so it doesn't overwrite the display:none that x-show manages.)
            this.panelStyle = window.innerWidth < 640
                ? { position: 'fixed', left: '12px', right: '12px', width: 'auto', marginTop: '0',
                    top: (this.$refs.bell.getBoundingClientRect().bottom + 8) + 'px' }
                : {};
            this.open = !this.open;
        },
     }"
     @resize.window="open = false"
     wire:poll.60s.visible="refreshNotifications"
     class="relative">
    {{-- Keep the number on the installed app's icon in sync with the unread count.
         The wire:key changes with the count, so Livewire replaces this element and
         x-init runs again after every refresh / mark-as-read. --}}
    <span wire:key="app-badge-{{ $this->unreadCount }}" class="hidden"
          x-init="if ('setAppBadge' in navigator) { ({{ $this->unreadCount }} > 0 ? navigator.setAppBadge({{ $this->unreadCount }}) : navigator.clearAppBadge()).catch(() => {}) }"></span>

    {{-- Bell Button --}}
    <button x-ref="bell" @click="toggle()" aria-label="Notifications" title="Notifications" class="relative p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-gray-300 transition">
        <x-phosphor-bell class="w-6 h-6" />

        @if ($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.outside="open = false" x-transition :style="panelStyle"
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50 overflow-hidden" style="display: none;">

        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Notifications</span>
            <div class="flex items-center gap-3">
                @if ($this->unreadCount > 0)
                    <button wire:click="markAllRead" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Mark all read</button>
                @endif
                @if ($this->hasRead)
                    <button wire:click="clearRead" wire:confirm="Delete all notifications you've already read?"
                            class="text-xs text-gray-500 dark:text-gray-400 hover:text-red-600 font-medium">Clear read</button>
                @endif
            </div>
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($this->notifications as $notification)
                <div wire:key="notif-{{ $notification->id }}" wire:click="markAsRead('{{ $notification->id }}')" class="relative px-4 py-3 pr-8 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ !$notification->read_at ? 'bg-indigo-50 dark:bg-indigo-950/30' : '' }}">
                    @if (! empty($notification->data['title']))
                        <p class="font-medium text-gray-900 dark:text-white">{{ $notification->data['title'] }}</p>
                    @endif
                    <p class="text-gray-700 dark:text-gray-200">{{ $notification->data['message'] ?? 'New notification' }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    {{-- .stop so removing doesn't also open the notification --}}
                    <button wire:click.stop="remove('{{ $notification->id }}')" title="Remove"
                            class="absolute top-0 right-0 p-2 text-gray-400 rounded hover:text-red-600">
                        <x-phosphor-x-bold class="w-3.5 h-3.5" />
                    </button>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No notifications yet.</div>
            @endforelse
        </div>

        {{-- Phone notifications toggle (script: layouts/_push-notifications) --}}
        @if (\App\Notifications\Channels\WebPushChannel::isConfigured())
            <div wire:ignore
                 x-data="{ state: 'loading', busy: false, testMsg: '' }"
                 x-init="window.gghiPush && window.gghiPush.status().then(s => state = s).catch(() => state = 'unsupported')"
                 class="px-4 py-3 text-xs border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <template x-if="state === 'off'">
                    <button @click="busy = true; gghiPush.enable().then(s => state = s).catch(() => alert('Could not turn on notifications. Please try again.')).finally(() => busy = false)"
                            :disabled="busy"
                            class="w-full px-3 py-2 font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                        <span x-text="busy ? 'Turning on…' : 'Turn on phone notifications'"></span>
                    </button>
                </template>
                <template x-if="state === 'on'">
                    <div class="space-y-2 text-gray-500 dark:text-gray-400">
                        <div class="flex items-center justify-between">
                            <span>✓ Notifications are on for this device</span>
                            <button @click="gghiPush.disable().then(s => state = s)" class="font-medium text-gray-500 underline hover:text-gray-700">Turn off</button>
                        </div>
                        <button @click="busy = true; testMsg = ''; gghiPush.test()
                                    .then(n => { testMsg = 'Sent to ' + n + ' device(s). A pop-up should appear within a few seconds.'; $wire.$refresh() })
                                    .catch(e => testMsg = e.message)
                                    .finally(() => busy = false)"
                                :disabled="busy"
                                class="w-full px-3 py-2 font-medium text-indigo-600 border border-indigo-200 rounded-lg dark:border-indigo-800 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 disabled:opacity-50">
                            <span x-text="busy ? 'Sending…' : 'Send test notification'"></span>
                        </button>
                        <p x-show="testMsg" x-text="testMsg" class="text-gray-500 dark:text-gray-400"></p>
                    </div>
                </template>
                <template x-if="state === 'denied'">
                    <p class="text-amber-600 dark:text-amber-400">Notifications are blocked. Allow them for this site in your browser settings.</p>
                </template>
                <template x-if="state === 'ios-install'">
                    <p class="text-gray-500 dark:text-gray-400">On iPhone: tap <strong>Share</strong> → <strong>Add to Home Screen</strong>, open the app from there, then turn on notifications.</p>
                </template>
                <template x-if="state === 'unsupported'">
                    <p class="text-gray-400">This browser doesn't support notifications.</p>
                </template>
            </div>
        @endif
    </div>
</div>
