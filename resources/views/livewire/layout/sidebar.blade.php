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
                ['label' => 'Dashboard',         'route' => 'admin.dashboard',           'icon' => 'home'],
                ['label' => 'Employees',          'route' => 'admin.employees',           'icon' => 'users'],
                ['label' => 'Payroll',            'route' => 'admin.payroll',             'icon' => 'currency-dollar'],
                ['label' => 'Salary Management',  'route' => 'admin.salary',              'icon' => 'banknotes'],
                ['label' => 'Deductions',          'route' => 'admin.deductions',          'icon' => 'document-text'],
                ['label' => 'Leave Approvals',    'route' => 'admin.leave',               'icon' => 'document-text'],
                ['label' => 'Leave Credits',      'route' => 'admin.leave-credits',       'icon' => 'check-circle'],
                ['label' => 'Day Offs',            'route' => 'admin.day-offs',             'icon' => 'calendar-days'],
                ['label' => 'Attendance Report',  'route' => 'admin.reports.attendance',  'icon' => 'calendar'],
                ['label' => 'Leave Report',       'route' => 'admin.reports.leave',       'icon' => 'chart-bar'],
                ['label' => 'Payroll Report',     'route' => 'admin.reports.payroll',     'icon' => 'banknotes'],
            ],
            'manager' => [
                ['label' => 'Dashboard',         'route' => 'admin.dashboard',          'icon' => 'home'],
                ['label' => 'Leave Approvals',   'route' => 'admin.leave',              'icon' => 'check-circle'],
                ['label' => 'Attendance Report', 'route' => 'admin.reports.attendance', 'icon' => 'calendar'],
                ['label' => 'Leave Report',      'route' => 'admin.reports.leave',      'icon' => 'chart-bar'],
            ],
            default => [
                ['label' => 'Dashboard',     'route' => 'dashboard',         'icon' => 'home'],
                ['label' => 'Attendance',    'route' => 'dashboard',         'icon' => 'calendar'],
                ['label' => 'My Payslips',   'route' => 'payslips.index',    'icon' => 'currency-dollar'],
                ['label' => 'Leave Request', 'route' => 'leave.request',     'icon' => 'paper-airplane'],
                ['label' => 'My Leaves',     'route' => 'leave.my-requests', 'icon' => 'document-text'],
                ['label' => 'Leave Balance', 'route' => 'leave.balance',     'icon' => 'check-circle'],
            ],
        };
    }
}; ?>

<aside
    x-data="{ open: false }"
    class="flex flex-col h-screen w-64 bg-gray-900 text-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-200 ease-in-out
           sm:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full sm:translate-x-0'"
>
    {{-- Logo / App Name --}}
    <div class="flex items-center justify-between px-4 py-3 bg-gray-800 shrink-0">
        <img src="{{ asset('images/gghi logo.png') }}" alt="GSAC General Hospital Inc." class="h-10 w-auto brightness-0 invert" />
    </div>

    {{-- Role Badge --}}
    <div class="px-6 py-3 bg-gray-800 border-b border-gray-700 shrink-0">
        <p class="text-xs text-gray-400 uppercase tracking-widest">{{ Auth::user()?->role }}</p>
        <p class="text-sm font-semibold text-white truncate">{{ Auth::user()?->name }}</p>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3">
        @foreach ($this->menuItems() as $item)
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                class="flex items-center gap-3 px-3 py-2 mb-1 rounded-lg text-sm font-medium transition-colors
                       {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}"
            >
                {{-- Heroicon outline (inline SVG by name) --}}
                @switch($item['icon'])
                    @case('home')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15.75a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H3.75A.75.75 0 013 21V9.75z"/></svg>
                        @break
                    @case('users')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        @break
                    @case('user-group')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        @break
                    @case('calendar')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        @break
                    @case('currency-dollar')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @break
                    @case('document-text')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        @break
                    @case('chart-bar')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        @break
                    @case('paper-airplane')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        @break
                    @case('check-circle')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @break
                    @case('banknotes')
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                        @break
                    @default
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                @endswitch

                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- User Footer --}}
    <div class="shrink-0 border-t border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()?->employee_code }}</p>
            </div>
            <button wire:click="logout" class="ml-2 text-gray-400 hover:text-red-400 transition-colors" title="Log out">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
            </button>
        </div>
    </div>
</aside>
