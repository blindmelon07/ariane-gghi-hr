<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function sectionIcon(string $section): string
    {
        return match ($section) {
            'Main'           => 'grid',
            'Payroll'        => 'currency-dollar',
            'Attendance'     => 'calendar',
            'Leave'          => 'document-check',
            'Fleet'          => 'truck',
            'Reports'        => 'chart-bar',
            'Administration' => 'shield-check',
            default          => 'dots',
        };
    }

    public function menuItems(): array
    {
        $role = Auth::user()?->role;

        return match ($role) {
            'super_admin' => [
                'Main' => [
                    ['label' => 'Dashboard',        'route' => 'admin.dashboard',          'icon' => 'home'],
                    ['label' => 'Employees',         'route' => 'admin.employees',          'icon' => 'users'],
                    ['label' => 'Departments',       'route' => 'admin.departments',        'icon' => 'office-building'],
                    ['label' => 'Positions',         'route' => 'admin.positions',          'icon' => 'briefcase'],
                ],
                'Payroll' => [
                    ['label' => 'Payroll',           'route' => 'admin.payroll',            'icon' => 'currency-dollar'],
                    ['label' => 'Salary Setup',      'route' => 'admin.salary',             'icon' => 'banknotes'],
                    ['label' => 'Deductions',        'route' => 'admin.deductions',         'icon' => 'minus-circle'],
                ],
                'Attendance' => [
                    ['label' => 'Schedules',         'route' => 'admin.schedules',          'icon' => 'clock'],
                    ['label' => 'Day Offs',          'route' => 'admin.day-offs',           'icon' => 'calendar-x'],
                    ['label' => 'Holidays',          'route' => 'admin.holidays',           'icon' => 'calendar'],
                    ['label' => 'Biometrics',        'route' => 'admin.biometrics',         'icon' => 'finger-print'],
                ],
                'Leave' => [
                    ['label' => 'Leave Approvals',      'route' => 'admin.leave',              'icon' => 'document-check'],
                    ['label' => 'Time Corrections',     'route' => 'admin.time-corrections',   'icon' => 'clock'],
                    ['label' => 'Leave Credits',        'route' => 'admin.leave-credits',      'icon' => 'gift'],
                ],
                'Fleet' => [
                    ['label' => 'Trip Requests',     'route' => 'admin.fleet-requests',     'icon' => 'document-text'],
                    ['label' => 'Vehicle Returns',   'route' => 'security.fleet-return',    'icon' => 'check-circle'],
                    ['label' => 'Vehicles',          'route' => 'admin.fleet-vehicles',     'icon' => 'truck'],
                    ['label' => 'Drivers',           'route' => 'admin.fleet-drivers',      'icon' => 'identification'],
                ],
                'Reports' => [
                    ['label' => 'Attendance',        'route' => 'admin.reports.attendance', 'icon' => 'chart-bar'],
                    ['label' => 'Leave',             'route' => 'admin.reports.leave',      'icon' => 'document-text'],
                    ['label' => 'Payroll',           'route' => 'admin.reports.payroll',    'icon' => 'banknotes'],
                    ['label' => 'Overtime',          'route' => 'admin.reports.overtime',   'icon' => 'clock'],
                ],
                'Administration' => [
                    ['label' => 'User Accounts',       'route' => 'admin.accounts',          'icon' => 'users'],
                    ['label' => 'Roles & Permissions', 'route' => 'admin.roles-permissions', 'icon' => 'shield-check'],
                ],
            ],
            'hr_admin' => [
                'Main' => [
                    ['label' => 'Dashboard',        'route' => 'admin.dashboard',          'icon' => 'home'],
                    ['label' => 'Employees',         'route' => 'admin.employees',          'icon' => 'users'],
                    ['label' => 'Departments',       'route' => 'admin.departments',        'icon' => 'office-building'],
                    ['label' => 'Positions',         'route' => 'admin.positions',          'icon' => 'briefcase'],
                ],
                'Payroll' => [
                    ['label' => 'Payroll',           'route' => 'admin.payroll',            'icon' => 'currency-dollar'],
                    ['label' => 'Salary Setup',      'route' => 'admin.salary',             'icon' => 'banknotes'],
                    ['label' => 'Deductions',        'route' => 'admin.deductions',         'icon' => 'minus-circle'],
                ],
                'Attendance' => [
                    ['label' => 'Schedules',         'route' => 'admin.schedules',          'icon' => 'clock'],
                    ['label' => 'Day Offs',          'route' => 'admin.day-offs',           'icon' => 'calendar-x'],
                    ['label' => 'Holidays',          'route' => 'admin.holidays',           'icon' => 'calendar'],
                    ['label' => 'Biometrics',        'route' => 'admin.biometrics',         'icon' => 'finger-print'],
                ],
                'Leave' => [
                    ['label' => 'Leave Approvals',      'route' => 'admin.leave',              'icon' => 'document-check'],
                    ['label' => 'Time Corrections',     'route' => 'admin.time-corrections',   'icon' => 'clock'],
                    ['label' => 'Leave Credits',        'route' => 'admin.leave-credits',      'icon' => 'gift'],
                ],
                'Fleet' => [
                    ['label' => 'Trip Requests',     'route' => 'admin.fleet-requests',     'icon' => 'document-text'],
                    ['label' => 'Vehicle Returns',   'route' => 'security.fleet-return',    'icon' => 'check-circle'],
                    ['label' => 'Vehicles',          'route' => 'admin.fleet-vehicles',     'icon' => 'truck'],
                    ['label' => 'Drivers',           'route' => 'admin.fleet-drivers',      'icon' => 'identification'],
                ],
                'Reports' => [
                    ['label' => 'Attendance',        'route' => 'admin.reports.attendance', 'icon' => 'chart-bar'],
                    ['label' => 'Leave',             'route' => 'admin.reports.leave',      'icon' => 'document-text'],
                    ['label' => 'Payroll',           'route' => 'admin.reports.payroll',    'icon' => 'banknotes'],
                    ['label' => 'Overtime',          'route' => 'admin.reports.overtime',   'icon' => 'clock'],
                ],
            ],
            'manager', 'department_head' => [
                'Main' => [
                    ['label' => 'Dashboard',         'route' => 'admin.dashboard',          'icon' => 'home'],
                    ['label' => 'Leave Approvals',   'route' => 'admin.leave',              'icon' => 'document-check'],
                    ['label' => 'Trip Requests',     'route' => 'admin.fleet-requests',     'icon' => 'truck'],
                ],
                'My Leave' => [
                    ['label' => 'File a Leave',      'route' => 'leave.request',            'icon' => 'paper-airplane'],
                    ['label' => 'My Requests',       'route' => 'leave.my-requests',        'icon' => 'document-text'],
                    ['label' => 'Leave Balance',     'route' => 'leave.balance',            'icon' => 'gift'],
                    ['label' => 'Time Correction',   'route' => 'time-correction.request',  'icon' => 'clock'],
                ],
                'Fleet' => [
                    ['label' => 'Request Trip',      'route' => 'trip-ticket.request',      'icon' => 'paper-airplane'],
                    ['label' => 'My Tickets',        'route' => 'trip-ticket.list',         'icon' => 'document-text'],
                ],
                'Reports' => [
                    ['label' => 'Attendance',        'route' => 'admin.reports.attendance', 'icon' => 'chart-bar'],
                    ['label' => 'Leave',             'route' => 'admin.reports.leave',      'icon' => 'document-text'],
                ],
            ],
            'approver' => [
                'Main' => [
                    ['label' => 'Dashboard',           'route' => 'admin.dashboard',          'icon' => 'home'],
                    ['label' => 'Leave Approvals',     'route' => 'admin.leave',              'icon' => 'document-check'],
                    ['label' => 'Time Corrections',    'route' => 'admin.time-corrections',   'icon' => 'clock'],
                    ['label' => 'Trip Requests',       'route' => 'admin.fleet-requests',     'icon' => 'truck'],
                ],
                'Reports' => [
                    ['label' => 'Attendance',          'route' => 'admin.reports.attendance', 'icon' => 'chart-bar'],
                    ['label' => 'Leave',               'route' => 'admin.reports.leave',      'icon' => 'document-text'],
                ],
            ],
            'security_guard' => [
                'Main' => [
                    ['label' => 'Vehicle Returns',   'route' => 'security.fleet-return',    'icon' => 'check-circle'],
                ],
            ],
            default => [
                'Main' => [
                    ['label' => 'Dashboard',         'route' => 'dashboard',                'icon' => 'home'],
                    ['label' => 'Attendance',        'route' => 'dashboard',                'icon' => 'calendar'],
                ],
                'Payroll' => [
                    ['label' => 'My Payslips',       'route' => 'payslips.index',           'icon' => 'currency-dollar'],
                ],
                'Leave' => [
                    ['label' => 'File a Leave',      'route' => 'leave.request',            'icon' => 'paper-airplane'],
                    ['label' => 'My Requests',       'route' => 'leave.my-requests',        'icon' => 'document-text'],
                    ['label' => 'Leave Balance',     'route' => 'leave.balance',            'icon' => 'gift'],
                ],
                'Attendance' => [
                    ['label' => 'Time Correction',   'route' => 'time-correction.request',  'icon' => 'clock'],
                ],
                'Fleet' => [
                    ['label' => 'Request Trip',      'route' => 'trip-ticket.request',      'icon' => 'paper-airplane'],
                    ['label' => 'My Tickets',        'route' => 'trip-ticket.list',         'icon' => 'document-text'],
                ],
            ],
        };
    }
}; ?>

<aside
    x-data
    class="app-sidebar flex flex-col h-screen bg-[#0d1b2e] text-white fixed inset-y-0 left-0 z-50 border-r border-white/5"
    :class="{ 'sidebar-open': $store.sidebar.mobileOpen }"
    style="width:16rem"
    :style="{ width: $store.sidebar.collapsed ? '4.5rem' : '16rem' }"
>
    {{-- ── Header: Logo + Toggle ── --}}
    <div class="flex items-center h-16 shrink-0 px-4 border-b border-white/5"
         :class="$store.sidebar.collapsed ? 'justify-center px-0' : 'justify-between px-4'">

        <div x-show="!$store.sidebar.collapsed"
             x-transition:enter="transition-opacity duration-200 delay-75"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-100"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="overflow-hidden">
            <img src="{{ asset('images/gghi logo.png') }}" alt="{{ config('app.name') }}"
                 class="h-8 w-auto object-contain" />
        </div>

        <button @click="$store.sidebar.toggle()"
                class="flex items-center justify-center w-9 h-9 rounded-xl transition-colors
                       text-slate-400 hover:text-white hover:bg-white/10 shrink-0"
                :title="$store.sidebar.collapsed ? 'Expand' : 'Collapse'">
            <svg class="w-5 h-5 transition-transform duration-300"
                 :class="$store.sidebar.collapsed ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    {{-- ── User Avatar ── --}}
    <div class="shrink-0 border-b border-white/5"
         :class="$store.sidebar.collapsed ? 'py-4 flex justify-center' : 'px-4 py-4'">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0 shadow-lg">
                <span class="text-xs font-bold text-white">
                    {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 2)) }}
                </span>
            </div>
            <div x-show="!$store.sidebar.collapsed"
                 x-transition:enter="transition-opacity duration-200 delay-75"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-100"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="min-w-0 overflow-hidden">
                <p class="text-sm font-semibold text-white truncate leading-tight">{{ Auth::user()?->name }}</p>
                <p class="text-[11px] text-blue-400 capitalize mt-0.5">
                    {{ str_replace('_', ' ', Auth::user()?->role ?? '') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── Navigation ── --}}
    <nav class="flex-1 overflow-y-auto py-3 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
         :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">

        @foreach ($this->menuItems() as $section => $items)
            @php
                $sectionIcon = $this->sectionIcon($section);
                $isSingleItem = count($items) === 1;
                $sectionActive = collect($items)->contains(fn($i) => request()->routeIs($i['route']));
            @endphp

            @if ($isSingleItem)
                {{-- ── Single item: direct link ── --}}
                @php $item = $items[0]; $active = request()->routeIs($item['route']); @endphp
                <div class="relative mb-0.5" x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false">
                    <a href="{{ route($item['route']) }}" wire:navigate @click="$store.sidebar.closeMobile()"
                       class="flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 group"
                       :class="[
                           $store.sidebar.collapsed ? 'justify-center p-3' : 'px-3 py-2.5',
                           {{ $active ? 'true' : 'false' }} ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-white/8'
                       ]">
                        @include('livewire.layout._sidebar-icon', ['icon' => $item['icon'], 'active' => $active])
                        <span x-show="!$store.sidebar.collapsed"
                              x-transition:enter="transition-opacity duration-150 delay-75"
                              x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-100"
                              x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                              class="truncate font-medium">{{ $item['label'] }}</span>
                        @if ($active)
                            <span x-show="!$store.sidebar.collapsed" class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></span>
                        @endif
                    </a>
                    {{-- Collapsed tooltip --}}
                    <div x-show="$store.sidebar.collapsed && hover"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-x-1"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3 z-[200]
                                bg-[#1a2d4a] border border-white/10 rounded-xl shadow-2xl px-3 py-2 whitespace-nowrap pointer-events-none">
                        <p class="text-xs font-semibold text-white">{{ $item['label'] }}</p>
                    </div>
                </div>

            @else
                {{-- ── Group: collapsible section ── --}}
                <div class="mb-1" x-data="{ open: {{ $sectionActive ? 'true' : 'false' }}, hover: false }"
                     @mouseenter="hover = true" @mouseleave="hover = false">

                    {{-- Group trigger button --}}
                    <button @click="if (!$store.sidebar.collapsed) open = !open"
                            class="w-full flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 group"
                            :class="[
                                $store.sidebar.collapsed ? 'justify-center p-3' : 'px-3 py-2.5',
                                {{ $sectionActive ? 'true' : 'false' }} && !$store.sidebar.collapsed
                                    ? 'text-white'
                                    : 'text-slate-400 hover:text-white hover:bg-white/8'
                            ]">
                        @include('livewire.layout._sidebar-icon', ['icon' => $sectionIcon, 'active' => $sectionActive])
                        <span x-show="!$store.sidebar.collapsed"
                              x-transition:enter="transition-opacity duration-150 delay-75"
                              x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-100"
                              x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                              class="flex-1 text-left truncate font-medium">{{ $section }}</span>
                        {{-- Chevron --}}
                        <svg x-show="!$store.sidebar.collapsed"
                             :class="open ? 'rotate-180' : ''"
                             class="w-3.5 h-3.5 shrink-0 text-slate-500 transition-transform duration-200"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Expanded: sub-items inline --}}
                    <div x-show="open && !$store.sidebar.collapsed"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="mt-0.5 ml-3 pl-3 border-l border-white/10 space-y-0.5">
                        @foreach ($items as $item)
                            @php $active = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route']) }}" wire:navigate @click="$store.sidebar.closeMobile()"
                               class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition-all duration-150
                                      {{ $active
                                          ? 'text-blue-300 bg-blue-500/15 font-medium'
                                          : 'text-slate-500 hover:text-slate-200 hover:bg-white/6' }}">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $active ? 'bg-blue-400' : 'bg-slate-600' }}"></span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Collapsed: popup tooltip with all sub-items --}}
                    <div x-show="$store.sidebar.collapsed && hover"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-x-2"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         class="absolute left-[4.5rem] z-[200] pointer-events-none"
                         style="top: auto; margin-top: -3rem">
                        <div class="bg-[#1a2d4a] border border-white/10 rounded-2xl shadow-2xl py-2 min-w-[160px]">
                            <p class="px-4 pt-1 pb-2 text-[10px] font-bold text-blue-400 uppercase tracking-widest">
                                {{ $section }}
                            </p>
                            @foreach ($items as $item)
                                @php $active = request()->routeIs($item['route']); @endphp
                                <div class="flex items-center gap-2.5 px-4 py-2 text-sm
                                            {{ $active ? 'text-blue-300 font-medium' : 'text-slate-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-blue-400' : 'bg-slate-600' }} shrink-0"></span>
                                    {{ $item['label'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    {{-- ── Footer: Employee code only ── --}}
    <div class="shrink-0 border-t border-white/5 px-4 py-3"
         x-show="!$store.sidebar.collapsed"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-100"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <p class="text-[11px] text-slate-500 truncate">
            <span class="font-mono">{{ Auth::user()?->employee_code }}</span>
            <span class="mx-1">·</span>
            <span class="text-green-500">●</span> Online
        </p>
    </div>
</aside>
