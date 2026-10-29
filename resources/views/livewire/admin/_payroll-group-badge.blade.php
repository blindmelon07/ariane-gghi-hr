@php
    $groupClasses = match ($period->payroll_group) {
        'nursing'  => 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300',
        'business' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
        default    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $groupClasses }}">{{ $period->groupLabel() }}</span>
