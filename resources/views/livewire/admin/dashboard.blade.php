<div>
    {{-- Welcome --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-4 sm:p-6 mb-4 sm:mb-6">
        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100">Welcome, {{ Auth::user()->name }}</h3>
        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }} · {{ now()->format('l, F j, Y') }}</p>
    </div>

    {{-- Stat Cards — 2 cols on mobile, 3 on md, 6 on xl --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4 mb-4 sm:mb-6">
        {{-- Active Employees --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-3 sm:p-5 flex items-center gap-2 sm:gap-4">
            <div class="p-2 sm:p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg shrink-0">
                <x-phosphor-users class="w-4 h-4 sm:w-6 sm:h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Employees</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100" wire:loading.class="opacity-50">{{ $this->totalActiveEmployees }}</p>
            </div>
        </div>

        {{-- Present Today --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-3 sm:p-5 flex items-center gap-2 sm:gap-4">
            <div class="p-2 sm:p-3 bg-green-100 dark:bg-green-900/30 rounded-lg shrink-0">
                <x-phosphor-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Present</p>
                <p class="text-xl sm:text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->presentToday }}</p>
            </div>
        </div>

        {{-- Absent Today --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-3 sm:p-5 flex items-center gap-2 sm:gap-4">
            <div class="p-2 sm:p-3 bg-red-100 dark:bg-red-900/30 rounded-lg shrink-0">
                <x-phosphor-prohibit class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Absent</p>
                <p class="text-xl sm:text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->absentToday }}</p>
            </div>
        </div>

        {{-- Late Today --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-3 sm:p-5 flex items-center gap-2 sm:gap-4">
            <div class="p-2 sm:p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg shrink-0">
                <x-phosphor-clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Late</p>
                <p class="text-xl sm:text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->lateToday }}</p>
            </div>
        </div>

        {{-- Pending Leaves --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-3 sm:p-5 flex items-center gap-2 sm:gap-4">
            <div class="p-2 sm:p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg shrink-0">
                <x-phosphor-file-text class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pending Leaves</p>
                <p class="text-xl sm:text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $this->pendingLeaves }}</p>
            </div>
        </div>

        {{-- Pending Payroll --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-3 sm:p-5 flex items-center gap-2 sm:gap-4">
            <div class="p-2 sm:p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg shrink-0">
                <x-phosphor-currency-circle-dollar class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pending Payroll</p>
                <p class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->pendingPayroll }}</p>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">

        {{-- Weekly Attendance Bar Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-4 sm:p-6"
             x-data="{
                chart: null,
                async init() {
                    await window.chartReady();
                    const isDark = document.documentElement.classList.contains('dark');
                    const gridColor  = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                    const labelColor = isDark ? '#94a3b8' : '#6b7280';
                    const data = @js($this->weeklyAttendanceChart);
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'bar',
                        data: {
                            labels: data.map(d => d.label),
                            datasets: [
                                {
                                    label: 'On Time',
                                    data: data.map(d => d.onTime),
                                    backgroundColor: 'rgba(99,102,241,0.8)',
                                    borderRadius: 4,
                                },
                                {
                                    label: 'Late',
                                    data: data.map(d => d.late),
                                    backgroundColor: 'rgba(234,179,8,0.8)',
                                    borderRadius: 4,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { labels: { color: labelColor, font: { size: 11 } } } },
                            scales: {
                                x: { stacked: true, ticks: { color: labelColor }, grid: { color: gridColor } },
                                y: { stacked: true, ticks: { color: labelColor, precision: 0 }, grid: { color: gridColor } },
                            },
                        },
                    });
                }
             }">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Weekly Attendance (Last 7 Days)</h4>
            <canvas x-ref="canvas" height="180"></canvas>
        </div>

        {{-- Leave Status Donut Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-4 sm:p-6"
             x-data="{
                chart: null,
                async init() {
                    await window.chartReady();
                    const isDark = document.documentElement.classList.contains('dark');
                    const labelColor = isDark ? '#94a3b8' : '#6b7280';
                    const raw = @js($this->leaveStatusChart);
                    const colorMap = { approved: '#22c55e', pending: '#eab308', rejected: '#ef4444' };
                    const labels = Object.keys(raw).map(k => k.charAt(0).toUpperCase() + k.slice(1));
                    const values = Object.values(raw);
                    const colors = Object.keys(raw).map(k => colorMap[k] ?? '#94a3b8');
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{ data: values, backgroundColor: colors, borderWidth: 2 }],
                        },
                        options: {
                            responsive: true,
                            cutout: '65%',
                            plugins: {
                                legend: { position: 'bottom', labels: { color: labelColor, font: { size: 11 }, padding: 16 } },
                            },
                        },
                    });
                }
             }">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Leave Requests {{ now()->year }}</h4>
            <div class="flex justify-center">
                <canvas x-ref="canvas" height="180" style="max-width:260px"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
        {{-- Quick Links --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Quick Actions</h4>
            <div class="space-y-2">
                @if ($this->canManageHr)
                <button wire:click="syncBioTime" class="w-full flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <x-phosphor-arrows-clockwise class="w-5 h-5 text-indigo-500 dark:text-indigo-400" />
                    <span wire:loading.remove wire:target="syncBioTime">Sync BioTime Employees</span>
                    <span wire:loading wire:target="syncBioTime">Syncing...</span>
                </button>
                <a href="{{ route('admin.payroll') }}" wire:navigate class="w-full flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <x-phosphor-currency-circle-dollar class="w-5 h-5 text-green-500 dark:text-green-400" />
                    Process Payroll
                </a>
                @endif
                <a href="{{ route('admin.reports.attendance') }}" wire:navigate class="w-full flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <x-phosphor-chart-bar class="w-5 h-5 text-yellow-500 dark:text-yellow-400" />
                    Attendance Reports
                </a>
            </div>
        </div>

        {{-- Today's Birthdays --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">
                🎂 Birthdays Today
            </h4>
            @forelse ($this->todayBirthdays as $emp)
                <div class="flex items-center gap-3 py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="w-8 h-8 bg-pink-100 dark:bg-pink-900/30 rounded-full flex items-center justify-center text-pink-600 dark:text-pink-400 text-xs font-bold">
                        {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $emp->full_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $emp->department }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500">No birthdays today.</p>
            @endforelse
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-transparent dark:border-gray-700 p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Recent Activity</h4>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse ($this->recentActivity as $log)
                    <div class="flex items-start gap-3 text-sm {{ !$loop->last ? 'pb-3 border-b border-gray-100 dark:border-gray-700' : '' }}">
                        <div class="mt-0.5 w-2 h-2 rounded-full shrink-0
                            @switch($log->action)
                                @case('leave_approved') bg-green-500 @break
                                @case('leave_rejected') bg-red-500 @break
                                @case('payroll_finalized') bg-blue-500 @break
                                @case('employees_synced') bg-indigo-500 @break
                                @default bg-gray-400 dark:bg-gray-500
                            @endswitch
                        "></div>
                        <div class="min-w-0">
                            <p class="text-gray-700 dark:text-gray-300 truncate">{{ $log->description }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 dark:text-gray-500">No recent activity.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
