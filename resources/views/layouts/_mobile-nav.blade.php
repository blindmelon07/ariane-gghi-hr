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
        in_array($role, ['security_guard']) => [
            ['route' => 'security.fleet-return',     'label' => 'Returns', 'icon' => 'check'],
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
                    @php
                        $phIcon = [
                            'home'     => 'house',
                            'users'    => 'users',
                            'leave'    => 'seal-check',
                            'payroll'  => 'currency-circle-dollar',
                            'requests' => 'file-text',
                            'payslip'  => 'money',
                            'chart'    => 'chart-bar',
                            'check'    => 'check-circle',
                        ][$item['icon']] ?? 'user';
                    @endphp
                    <x-dynamic-component :component="'phosphor-' . $phIcon . ($active ? '-fill' : '')" class="w-5 h-5" />
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
