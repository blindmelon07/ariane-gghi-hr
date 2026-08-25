@auth
@php
    $role = auth()->user()?->role;
    $items = match(true) {
        in_array($role, ['super_admin', 'hr_admin']) => [
            ['route' => 'admin.dashboard',  'label' => 'Home',    'icon' => 'home'],
            ['route' => 'admin.employees',  'label' => 'Staff',   'icon' => 'users'],
            ['route' => 'admin.leave',      'label' => 'Leave',   'icon' => 'leave'],
            ['route' => 'admin.payroll',    'label' => 'Payroll', 'icon' => 'payroll'],
            ['route' => 'profile',          'label' => 'Profile', 'icon' => 'profile'],
        ],
        in_array($role, ['manager', 'department_head']) => [
            ['route' => 'admin.dashboard',           'label' => 'Home',     'icon' => 'home'],
            ['route' => 'admin.leave',               'label' => 'Approvals','icon' => 'leave'],
            ['route' => 'leave.request',             'label' => 'My Leave', 'icon' => 'requests'],
            ['route' => 'profile',                   'label' => 'Profile',  'icon' => 'profile'],
        ],
        in_array($role, ['approver']) => [
            ['route' => 'admin.dashboard',           'label' => 'Home',    'icon' => 'home'],
            ['route' => 'admin.leave',               'label' => 'Leave',   'icon' => 'leave'],
            ['route' => 'admin.reports.attendance',  'label' => 'Reports', 'icon' => 'chart'],
            ['route' => 'profile',                   'label' => 'Profile', 'icon' => 'profile'],
        ],
        in_array($role, ['head_nurse']) => [
            ['route' => 'head-nurse.roster',         'label' => 'Roster',  'icon' => 'chart'],
            ['route' => 'leave.request',             'label' => 'Leave',   'icon' => 'leave'],
            ['route' => 'payslips.index',            'label' => 'Payslips','icon' => 'payslip'],
            ['route' => 'profile',                   'label' => 'Profile', 'icon' => 'profile'],
        ],
        default => [
            ['route' => 'dashboard',         'label' => 'Home',     'icon' => 'home'],
            ['route' => 'leave.request',     'label' => 'Leave',    'icon' => 'leave'],
            ['route' => 'leave.my-requests', 'label' => 'Requests', 'icon' => 'requests'],
            ['route' => 'payslips.index',    'label' => 'Payslips', 'icon' => 'payslip'],
            ['route' => 'profile',           'label' => 'Profile',  'icon' => 'profile'],
        ],
    };
@endphp

{{-- Bottom Navigation — mobile only (hidden on sm+) --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-[#0a1628] border-t border-blue-800/60"
     style="padding-bottom: env(safe-area-inset-bottom, 0px)">
    <div class="flex items-stretch h-16">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               wire:navigate
               class="relative flex flex-col items-center justify-center flex-1 gap-1 px-1
                      transition-colors duration-150 active:scale-95
                      {{ $active ? 'text-blue-400' : 'text-slate-500 hover:text-slate-300' }}">

                {{-- Active indicator bar --}}
                @if ($active)
                    <span class="absolute top-0 inset-x-3 h-0.5 rounded-b-full bg-blue-400"></span>
                @endif

                {{-- Icon --}}
                <span class="w-5 h-5 shrink-0">
                    @switch($item['icon'])
                        @case('home')
                            <svg fill="{{ $active ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15.75a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H3.75A.75.75 0 013 21V9.75z"/>
                            </svg>
                            @break
                        @case('users')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                            </svg>
                            @break
                        @case('leave')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M9 12.75L11.25 15 15 9.75M7.5 21h9a2.25 2.25 0 002.25-2.25V6.108c0-.6-.237-1.176-.659-1.598l-2.1-2.1A2.25 2.25 0 0013.41 1.5H7.5A2.25 2.25 0 005.25 3.75v15A2.25 2.25 0 007.5 21z"/>
                            </svg>
                            @break
                        @case('payroll')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            @break
                        @case('requests')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                            @break
                        @case('payslip')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                            </svg>
                            @break
                        @case('chart')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                            @break
                        @default
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $active ? 2 : 1.75 }}" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                    @endswitch
                </span>

                {{-- Label --}}
                <span class="text-[10px] font-medium leading-none tracking-wide truncate w-full text-center">
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</nav>
@endauth
