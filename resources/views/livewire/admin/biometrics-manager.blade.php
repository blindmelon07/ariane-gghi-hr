<div class="space-y-6">

    {{-- Alerts --}}
    @if ($successMessage)
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 text-sm">
            <x-phosphor-check-circle-bold class="w-4 h-4 shrink-0" />
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm">
            <x-phosphor-warning-circle-bold class="w-4 h-4 shrink-0" />
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Top row: connection card + stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ZKTeco connection card --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6">
            @if (!$this->deviceConfigured())
                <div class="flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 text-sm">
                    <x-phosphor-info-bold class="w-4 h-4 shrink-0" />
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
                <x-phosphor-circle-notch wire:loading wire:target="testConnection" class="animate-spin w-4 h-4" />
                <x-phosphor-lightning-bold wire:loading.remove wire:target="testConnection" class="w-4 h-4" />
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
                        <label for="fromDate" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">From</label>
                        <input id="fromDate" type="date" wire:model.live="fromDate"
                               class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                        @error('fromDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="toDate" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">To</label>
                        <input id="toDate" type="date" wire:model.live="toDate"
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
                <x-phosphor-circle-notch wire:loading wire:target="syncAttendance" class="animate-spin w-4 h-4" />
                <x-phosphor-arrows-clockwise-bold wire:loading.remove wire:target="syncAttendance" class="w-4 h-4" />
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
                <x-phosphor-circle-notch wire:loading wire:target="syncUsers" class="animate-spin w-4 h-4" />
                <x-phosphor-users-three-bold wire:loading.remove wire:target="syncUsers" class="w-4 h-4" />
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

    {{-- Manual attendance import — for environments without a live device connection (e.g. the online server) --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Import Attendance (Excel)</h2>
        <p class="text-xs text-gray-400 dark:text-slate-500 mb-5">
            Upload the ZKTeco "Employee Attendance Record Table" export (one sheet named "RecordTable"). Use this when the server can't reach the device directly — e.g. the online environment, where sync is normally pushed from the local machine.
        </p>

        <form wire:submit="importAttendance" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="importPeriodStart" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cutoff Start Date</label>
                    <input id="importPeriodStart" type="date" wire:model="importPeriodStart"
                           class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                    <p class="text-[11px] text-slate-400 mt-1">First day shown in the sheet — its month/year is used to resolve every day-of-month column.</p>
                    @error('importPeriodStart') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="importFile" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Excel File</label>
                    <input id="importFile" type="file" wire:model="importFile" accept=".xlsx,.xls"
                           class="w-full text-sm text-gray-700 dark:text-gray-200" />
                    <div wire:loading wire:target="importFile" class="text-xs text-gray-400 dark:text-slate-500 mt-1">Uploading…</div>
                    @error('importFile') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="importAttendance"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white text-sm font-medium transition-colors"
            >
                <x-phosphor-circle-notch wire:loading wire:target="importAttendance" class="animate-spin w-4 h-4" />
                <x-phosphor-download-simple-bold wire:loading.remove wire:target="importAttendance" class="w-4 h-4" />
                Import Attendance
            </button>
        </form>
    </div>

    {{-- Sync History --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sync History</h2>
                <p class="text-xs text-slate-400 mt-0.5">Last 15 sync jobs run by the scheduler or triggered manually.</p>
            </div>
        </div>

        {{-- Cards (mobile) --}}
        <div class="lg:hidden divide-y divide-gray-50 dark:divide-slate-800">
            @forelse ($this->recentSyncLogs as $log)
                <div class="px-6 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <span @class([
                            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize',
                            'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => $log->type === 'attendance',
                            'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400'                 => $log->type === 'employees',
                        ])>
                            {{ $log->type }}
                        </span>
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
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-400 dark:text-slate-500">
                        <span>{{ $log->started_at->format('M d, Y H:i:s') }}</span>
                        <span>{{ $log->records_fetched > 0 ? number_format($log->records_fetched) . ' records' : '—' }}</span>
                    </div>
                    @if ($log->error_message)
                        <p class="mt-1 text-xs text-red-500 dark:text-red-400 truncate">{{ $log->error_message }}</p>
                    @endif
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-400">
                    No sync history yet. Jobs will appear here once they run.
                </div>
            @endforelse
        </div>

        {{-- Table (desktop / tablet) --}}
        <div class="hidden lg:block overflow-x-auto">
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

        {{-- Cards (mobile) --}}
        <div class="lg:hidden divide-y divide-gray-50 dark:divide-slate-800">
            @forelse ($this->deviceUsers as $emp)
                <div class="px-6 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ $emp->full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 font-mono">{{ $emp->emp_code }}</p>
                        </div>
                        @if ($emp->user)
                            <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $emp->user->is_active ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $emp->user->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                {{ $emp->user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                No Account
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-400 dark:text-slate-500">
                        <span>{{ $emp->user ? ucfirst($emp->user->role) : 'No role' }}</span>
                        <span>Synced {{ $emp->synced_at?->format('M d, Y H:i') ?? '—' }}</span>
                    </div>
                    <button wire:click="openAccountModal({{ $emp->id }})"
                        class="mt-2 w-full {{ $emp->user ? 'tbl-action' : 'tbl-action tbl-action-primary' }}">
                        {{ $emp->user ? 'Manage Account' : 'Assign Account' }}
                    </button>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-400">
                    No employees yet. Click <strong>Sync Employees from BioTime</strong> above to import them.
                </div>
            @endforelse
        </div>

        {{-- Table (desktop / tablet) --}}
        <div class="hidden lg:block overflow-x-auto">
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
                                    class="{{ $emp->user ? 'tbl-action' : 'tbl-action tbl-action-primary' }}">
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
                        <x-phosphor-x-bold class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="accountRole" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Role</label>
                        <select id="accountRole" wire:model.live="accountRole"
                            class="w-full rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="hr_admin">HR Admin</option>
                        </select>
                        @error('accountRole') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="accountPassword" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">
                            Password {{ $hasExistingAccount ? '(leave blank to keep current)' : '' }}
                        </label>
                        <input id="accountPassword" type="password" wire:model.live="accountPassword"
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

        {{-- Cards (mobile) --}}
        <div class="lg:hidden divide-y divide-gray-50 dark:divide-slate-800">
            @forelse ($this->recentLogs as $log)
                <div class="px-6 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ $log->employee?->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 font-mono">{{ $log->emp_code }}</p>
                        </div>
                        <span @class([
                            'shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => $log->punch_state == 0,
                            'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400'            => $log->punch_state == 1,
                            'bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400'             => !in_array($log->punch_state, [0, 1]),
                        ])>
                            {{ $log->punch_state == 0 ? 'Check In' : ($log->punch_state == 1 ? 'Check Out' : 'Other') }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-400 dark:text-slate-500">
                        <span>{{ \Carbon\Carbon::parse($log->punch_time)->format('M d, Y H:i:s') }}</span>
                        @if ($log->is_processed)
                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                <x-phosphor-check-bold class="w-3.5 h-3.5" />
                                Processed
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-slate-400">
                                <x-phosphor-clock-bold class="w-3.5 h-3.5" />
                                Pending
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-400">
                    No attendance logs yet. Sync from BioTime to import records.
                </div>
            @endforelse
        </div>

        {{-- Table (desktop / tablet) --}}
        <div class="hidden lg:block overflow-x-auto">
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
                                        <x-phosphor-check-bold class="w-3.5 h-3.5" />
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                        <x-phosphor-clock-bold class="w-3.5 h-3.5" />
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
