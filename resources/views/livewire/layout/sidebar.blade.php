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

    public function menuItems(): array
    {
        $role = Auth::user()?->role;

        return match ($role) {
            'hr_admin' => [
                'Main' => [
                    ['label' => 'Dashboard',        'route' => 'admin.dashboard',          'icon' => 'home'],
                    ['label' => 'Employees',         'route' => 'admin.employees',          'icon' => 'users'],
                ],
                'Payroll' => [
                    ['label' => 'Payroll',           'route' => 'admin.payroll',            'icon' => 'currency-dollar'],
                    ['label' => 'Salary Setup',      'route' => 'admin.salary',             'icon' => 'banknotes'],
                    ['label' => 'Deductions',        'route' => 'admin.deductions',         'icon' => 'minus-circle'],
                ],
                'Attendance' => [
                    ['label' => 'Schedules',         'route' => 'admin.schedules',          'icon' => 'clock'],
                    ['label' => 'Day Offs',          'route' => 'admin.day-offs',           'icon' => 'calendar-x'],
                ],
                'Leave' => [
                    ['label' => 'Leave Approvals',   'route' => 'admin.leave',              'icon' => 'document-check'],
                    ['label' => 'Leave Credits',     'route' => 'admin.leave-credits',      'icon' => 'gift'],
                ],
                'Reports' => [
                    ['label' => 'Attendance',        'route' => 'admin.reports.attendance', 'icon' => 'chart-bar'],
                    ['label' => 'Leave',             'route' => 'admin.reports.leave',      'icon' => 'document-text'],
                    ['label' => 'Payroll',           'route' => 'admin.reports.payroll',    'icon' => 'banknotes'],
                ],
            ],
            'manager' => [
                'Main' => [
                    ['label' => 'Dashboard',         'route' => 'admin.dashboard',          'icon' => 'home'],
                    ['label' => 'Leave Approvals',   'route' => 'admin.leave',              'icon' => 'document-check'],
                ],
                'Reports' => [
                    ['label' => 'Attendance',        'route' => 'admin.reports.attendance', 'icon' => 'chart-bar'],
                    ['label' => 'Leave',             'route' => 'admin.reports.leave',      'icon' => 'document-text'],
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
            ],
        };
    }
}; ?>

<aside
    x-data="{ open: false }"
    class="flex flex-col h-screen bg-[#0f172a] text-white fixed inset-y-0 left-0 z-50
           border-r border-slate-800 transition-all duration-300 ease-in-out"
    :class="{
        'w-64':  !$store.sidebar.collapsed,
        'w-16':   $store.sidebar.collapsed,
        'translate-x-0':           open || !$store.sidebar.collapsed || window.innerWidth >= 640,
        '-translate-x-full':       !open && window.innerWidth < 640,
    }"
>
    {{-- ── Brand / Toggle row (h-14 matches header) ── --}}
    <div class="h-14 flex items-center shrink-0 border-b border-slate-800 overflow-hidden relative">

        {{-- Logo — hides when collapsed --}}
        <div class="flex-1 flex items-center justify-center px-3 overflow-hidden"
             x-show="!$store.sidebar.collapsed"
             x-transition:enter="transition-opacity duration-200 delay-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
        >
            <img src="{{ asset('images/gghi logo.png') }}"
                 alt="{{ config('app.name', 'HR Portal') }}"
                 class="h-9 w-auto object-contain" />
        </div>

        {{-- Collapsed: just an icon placeholder --}}
        <div class="absolute inset-0 flex items-center justify-center"
             x-show="$store.sidebar.collapsed"
             x-transition:enter="transition-opacity duration-200 delay-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
        >
            <div class="w-8 h-8 rounded-lg bg-indigo-600/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>

        {{-- Toggle chevron button — floats on the right edge --}}
        <button
            @click="$store.sidebar.toggle()"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md
                   text-slate-500 hover:text-white hover:bg-white/10 transition-colors z-10"
            :title="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
            <svg class="w-4 h-4 transition-transform duration-300"
                 :class="$store.sidebar.collapsed ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    {{-- ── User Profile ── --}}
    <div class="border-b border-slate-800 shrink-0 overflow-hidden"
         :class="$store.sidebar.collapsed ? 'py-3 px-2' : 'px-4 py-3'">
        <div class="flex items-center gap-3">
            {{-- Avatar always visible --}}
            <div class="w-8 h-8 rounded-full bg-indigo-600/30 border border-indigo-500/50
                        flex items-center justify-center shrink-0 mx-auto"
                 :class="$store.sidebar.collapsed ? 'mx-auto' : ''">
                <span class="text-xs font-bold text-indigo-300">
                    {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 2)) }}
                </span>
            </div>
            {{-- Name + role — hidden when collapsed --}}
            <div class="min-w-0 flex-1 overflow-hidden"
                 x-show="!$store.sidebar.collapsed"
                 x-transition:enter="transition-opacity duration-200 delay-75"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
            >
                <p class="text-sm font-semibold text-white truncate leading-tight">{{ Auth::user()?->name }}</p>
                <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">
                    {{ str_replace('_', ' ', Auth::user()?->role ?? '') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── Navigation ── --}}
    <nav class="flex-1 overflow-y-auto py-3 space-y-4"
         :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">
        @foreach ($this->menuItems() as $section => $items)
            <div>
                {{-- Section label — hidden when collapsed --}}
                <p class="px-3 mb-1 text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500 whitespace-nowrap overflow-hidden"
                   x-show="!$store.sidebar.collapsed"
                   x-transition:enter="transition-opacity duration-150"
                   x-transition:enter-start="opacity-0"
                   x-transition:enter-end="opacity-100"
                   x-transition:leave="transition-opacity duration-100"
                   x-transition:leave-start="opacity-100"
                   x-transition:leave-end="opacity-0"
                >{{ $section }}</p>

                @foreach ($items as $item)
                    @php $active = request()->routeIs($item['route']); @endphp

                    <a href="{{ route($item['route']) }}"
                       wire:navigate
                       class="group relative flex items-center gap-3 py-2 mb-0.5 rounded-lg text-sm font-medium transition-all duration-150"
                       :class="{
                           'px-3':    !$store.sidebar.collapsed,
                           'px-0 justify-center': $store.sidebar.collapsed,
                           'bg-indigo-600 text-white shadow-lg shadow-indigo-900/40': {{ $active ? 'true' : 'false' }},
                           'text-slate-400 hover:text-white hover:bg-white/8': {{ !$active ? 'true' : 'false' }},
                       }"
                    >
                        {{-- Icon --}}
                        <span class="shrink-0 {{ $active ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300' }}">
                            @switch($item['icon'])
                                @case('home')         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15.75a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H3.75A.75.75 0 013 21V9.75z"/></svg> @break
                                @case('users')        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg> @break
                                @case('calendar')     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg> @break
                                @case('calendar-x')   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5M9.75 12.75l4.5 4.5m0-4.5l-4.5 4.5"/></svg> @break
                                @case('currency-dollar') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                                @case('banknotes')    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg> @break
                                @case('minus-circle') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                                @case('document-text') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg> @break
                                @case('document-check') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75M7.5 21h9a2.25 2.25 0 002.25-2.25V6.108c0-.6-.237-1.176-.659-1.598l-2.1-2.1A2.25 2.25 0 0013.41 1.5H7.5A2.25 2.25 0 005.25 3.75v15A2.25 2.25 0 007.5 21z"/></svg> @break
                                @case('chart-bar')    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg> @break
                                @case('paper-airplane') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg> @break
                                @case('gift')         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1014.25 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 109.75 7.5H12m0 0H7.5m4.5 0h4.5M12 7.5v13.5m4.5-13.5H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H12"/></svg> @break
                                @case('clock')        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                                @default              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                            @endswitch
                        </span>

                        {{-- Label — fades out when collapsed --}}
                        <span class="truncate whitespace-nowrap overflow-hidden transition-all duration-200"
                              x-show="!$store.sidebar.collapsed"
                              x-transition:enter="transition-opacity duration-200 delay-75"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-100"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                        >{{ $item['label'] }}</span>

                        @if ($active)
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-indigo-300 shrink-0"
                                  x-show="!$store.sidebar.collapsed"></span>
                        @endif

                        {{-- Collapsed tooltip --}}
                        <div class="absolute left-full ml-3 px-2.5 py-1.5 rounded-lg bg-slate-800 text-xs text-white
                                    whitespace-nowrap shadow-lg pointer-events-none z-50
                                    opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                             x-show="$store.sidebar.collapsed"
                             style="display:none"
                        >
                            {{ $item['label'] }}
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- ── Footer: logout ── --}}
    <div class="shrink-0 border-t border-slate-800 p-3">
        <div class="flex items-center gap-3"
             :class="$store.sidebar.collapsed ? 'justify-center' : 'px-2'">

            {{-- Employee code — hidden when collapsed --}}
            <div class="min-w-0 flex-1 overflow-hidden"
                 x-show="!$store.sidebar.collapsed"
                 x-transition:enter="transition-opacity duration-200 delay-75"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
            >
                <p class="text-xs font-medium text-slate-300 truncate">{{ Auth::user()?->employee_code }}</p>
                <p class="text-[10px] text-slate-500 mt-0.5">Logged in</p>
            </div>

            <button
                wire:click="logout"
                wire:confirm="Are you sure you want to log out?"
                class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-colors shrink-0"
                title="Log out">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
            </button>
        </div>
    </div>
</aside>
