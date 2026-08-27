<div class="space-y-6">

    {{-- Alerts --}}
    @if ($successMessage)
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Top row: connection card + stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ZKTeco connection card --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6">
            @if (!$this->deviceConfigured())
                <div class="flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    ZKTeco sync is managed by the local machine. Attendance data is pushed here automatically every 15 minutes.
                </div>
            @endif
            <div class="flex items-start justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Device Connection</h2>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                        {{ config('zkteco.ip') ?: 'IP not configured' }}
                        @if(config('zkteco.ip')) :{{ config('zkteco.port', 4370) }} @endif
                    </p>
                </div>

                @if ($connectionStatus)
                    <span @class([
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => $connectionStatus['connected'],
                        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'                 => !$connectionStatus['connected'],
                    ])>
                        <span @class([
                            'w-1.5 h-1.5 rounded-full',
                            'bg-emerald-500' => $connectionStatus['connected'],
                            'bg-red-500'     => !$connectionStatus['connected'],
                        ])></span>
                        {{ $connectionStatus['connected'] ? 'Online' : 'Offline' }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        Unknown
                    </span>
                @endif
            </div>

            @if (($connectionStatus['connected'] ?? false) && isset($connectionStatus['serial']))
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Serial</p>
                        <p class="text-sm font-mono font-medium text-gray-800 dark:text-gray-200">{{ $connectionStatus['serial'] }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">IP Address</p>
                        <p class="text-sm font-mono font-medium text-gray-800 dark:text-gray-200">{{ $connectionStatus['ip'] }}</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Port</p>
                        <p class="text-sm font-mono font-medium text-gray-800 dark:text-gray-200">{{ $connectionStatus['port'] }}</p>
                    </div>
                </div>
            @endif

            @if ($this->deviceConfigured())
            <button
                wire:click="testConnection"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium transition-colors"
            >
                <svg wire:loading wire:target="testConnection" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg wire:loading.remove wire:target="testConnection" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Test Connection
            </button>
            @endif
        </div>

        {{-- Stats column --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-5">
                <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Today's Punches</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->todayCount }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-5">
                <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Total Logs</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->totalLogs) }}</p>
            </div>
        </div>
    </div>

    {{-- Sync controls — only shown when ZKTeco device is configured locally --}}
    @if ($this->deviceConfigured())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Attendance sync --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Sync Attendance</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-5">Dispatch a background job to pull punch records from the ZKTeco device for a date range.</p>

            <div class="space-y-3 mb-5">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">From</label>
                        <input type="date" wire:model.live="fromDate"
                               class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                        @error('fromDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">To</label>
                        <input type="date" wire:model.live="toDate"
                               class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                        @error('toDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <button
                wire:click="syncAttendance"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-medium transition-colors"
            >
                <svg wire:loading wire:target="syncAttendance" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg wire:loading.remove wire:target="syncAttendance" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sync Attendance
            </button>
        </div>

        {{-- Employee/user sync --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Sync Employees</h2>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-5">Dispatch a background job to import employees from the ZKTeco device.</p>

            <button
                wire:click="syncUsers"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white text-sm font-medium transition-colors"
            >
                <svg wire:loading wire:target="syncUsers" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg wire:loading.remove wire:target="syncUsers" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Sync Employees from BioTime
            </button>

            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-800">
                <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed">
                    Employee names from the device will be used as first/last name. Review imported employees in the
                    <a href="{{ route('admin.employees') }}" wire:navigate class="text-indigo-500 hover:underline">Employees</a> page.
                </p>
            </div>
        </div>
    </div>

    @endif

    {{-- Sync History --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sync History</h2>
                <p class="text-xs text-slate-400 mt-0.5">Last 15 sync jobs run by the scheduler or triggered manually.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400 border-b border-gray-100 dark:border-slate-800">
                        <th class="text-left px-6 py-3 font-medium">Type</th>
                        <th class="text-left px-6 py-3 font-medium">Status</th>
                        <th class="text-left px-6 py-3 font-medium">Records</th>
                        <th class="text-left px-6 py-3 font-medium">Started</th>
                        <th class="text-left px-6 py-3 font-medium">Duration</th>
                        <th class="text-left px-6 py-3 font-medium">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                    @forelse ($this->recentSyncLogs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-3">
                                <span @class([
                                    'inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize',
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => $log->type === 'attendance',
                                    'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400'                 => $log->type === 'employees',
                                ])>
                                    {{ $log->type }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <span @class([
                                    'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => $log->status === 'success',
                                    'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'                 => $log->status === 'failed',
                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'         => $log->status === 'running',
                                    'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400'     => $log->status === 'partial',
                                ])>
                                    <span @class([
                                        'w-1.5 h-1.5 rounded-full',
                                        'bg-emerald-500'              => $log->status === 'success',
                                        'bg-red-500'                  => $log->status === 'failed',
                                        'bg-amber-400 animate-pulse'  => $log->status === 'running',
                                        'bg-orange-500'                => $log->status === 'partial',
                                    ])></span>
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-slate-300">
                                {{ $log->records_fetched > 0 ? number_format($log->records_fetched) : '—' }}
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ $log->started_at->format('M d, Y H:i:s') }}
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-400 dark:text-slate-500">
                                @if ($log->completed_at)
                                    {{ $log->started_at->diffInSeconds($log->completed_at) }}s
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-3 text-xs text-red-500 dark:text-red-400 max-w-xs truncate">
                                {{ $log->error_message ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">
                                No sync history yet. Jobs will appear here once they run.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Device Users --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">BioTime Employees</h2>
                <p class="text-xs text-slate-400 mt-0.5">Employees imported from BioTime. Assign an account so they can log in.</p>
            </div>
            <span class="text-xs font-medium text-slate-400">{{ $this->deviceUsers->count() }} employees</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400 border-b border-gray-100 dark:border-slate-800">
                        <th class="text-left px-6 py-3 font-medium">Emp Code</th>
                        <th class="text-left px-6 py-3 font-medium">Name</th>
                        <th class="text-left px-6 py-3 font-medium">Account</th>
                        <th class="text-left px-6 py-3 font-medium">Role</th>
                        <th class="text-left px-6 py-3 font-medium">Synced At</th>
                        <th class="text-right px-6 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                    @forelse ($this->deviceUsers as $emp)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $emp->emp_code }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $emp->full_name }}</td>
                            <td class="px-6 py-3">
                                @if ($emp->user)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $emp->user->is_active ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $emp->user->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                        {{ $emp->user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        No Account
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-500 dark:text-slate-400">
                                {{ $emp->user ? ucfirst($emp->user->role) : '—' }}
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-400 dark:text-slate-500">
                                {{ $emp->synced_at?->format('M d, Y H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                <button wire:click="openAccountModal({{ $emp->id }})"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors {{ $emp->user ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                    {{ $emp->user ? 'Manage Account' : 'Assign Account' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">
                                No employees yet. Click <strong>Sync Employees from BioTime</strong> above to import them.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Account Modal --}}
    @if ($showAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeAccountModal"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 p-6">

                <div class="flex items-start justify-between mb-5">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $hasExistingAccount ? 'Manage Account' : 'Assign Account' }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $accountName }} &middot; <span class="font-mono">{{ $accountEmpCode }}</span></p>
                    </div>
                    <button wire:click="closeAccountModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Role</label>
                        <select wire:model.live="accountRole"
                            class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="hr_admin">HR Admin</option>
                        </select>
                        @error('accountRole') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">
                            Password {{ $hasExistingAccount ? '(leave blank to keep current)' : '' }}
                        </label>
                        <input type="password" wire:model.live="accountPassword"
                            placeholder="{{ $hasExistingAccount ? 'Enter new password to change' : 'Set a password' }}"
                            class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                        @error('accountPassword') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button wire:click="closeAccountModal"
                        class="px-4 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="saveAccount" wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium transition-colors">
                        <span wire:loading.remove wire:target="saveAccount">{{ $hasExistingAccount ? 'Update Account' : 'Create Account' }}</span>
                        <span wire:loading wire:target="saveAccount">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Recent punch logs --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent Punch Logs</h2>
            <span class="text-xs text-slate-400">Last 15 records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400 border-b border-gray-100 dark:border-slate-800">
                        <th class="text-left px-6 py-3 font-medium">Employee</th>
                        <th class="text-left px-6 py-3 font-medium">Emp Code</th>
                        <th class="text-left px-6 py-3 font-medium">Punch Time</th>
                        <th class="text-left px-6 py-3 font-medium">State</th>
                        <th class="text-left px-6 py-3 font-medium">Processed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                    @forelse ($this->recentLogs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200">
                                {{ $log->employee?->full_name ?? '—' }}
                            </td>
                            <td class="px-6 py-3 font-mono text-gray-500 dark:text-slate-400 text-xs">
                                {{ $log->emp_code }}
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($log->punch_time)->format('M d, Y H:i:s') }}
                            </td>
                            <td class="px-6 py-3">
                                <span @class([
                                    'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => $log->punch_state == 0,
                                    'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400'            => $log->punch_state == 1,
                                    'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400'             => !in_array($log->punch_state, [0, 1]),
                                ])>
                                    {{ $log->punch_state == 0 ? 'Check In' : ($log->punch_state == 1 ? 'Check Out' : 'Other') }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                @if ($log->is_processed)
                                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">
                                No attendance logs yet. Sync from BioTime to import records.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
