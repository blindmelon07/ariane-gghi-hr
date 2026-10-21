@php
    $phIcon = [
        'grid'            => 'squares-four',
        'home'            => 'house',
        'users'           => 'users',
        'currency-dollar' => 'currency-circle-dollar',
        'calendar'        => 'calendar-blank',
        'document-check'  => 'seal-check',
        'chart-bar'       => 'chart-bar',
        'shield-check'    => 'shield-check',
        'banknotes'       => 'money',
        'clock'           => 'clock',
        'minus-circle'    => 'minus-circle',
        'gift'            => 'gift',
        'paper-airplane'  => 'paper-plane-right',
        'document-text'   => 'file-text',
        'finger-print'    => 'fingerprint',
        'calendar-x'      => 'calendar-x',
        'arrows-left-right' => 'arrows-left-right',
        'clock-countdown' => 'clock-countdown',
        'door'            => 'door-open',
        'first-aid'       => 'first-aid-kit',
        'clipboard'       => 'clipboard-text',
        'certificate'     => 'certificate',
        'office-building' => 'buildings',
        'briefcase'       => 'briefcase',
        'truck'           => 'truck',
        'check-circle'    => 'check-circle',
        'identification'  => 'identification-card',
        'chat-bubble'     => 'chat-dots',
    ][$icon] ?? 'dots-three-vertical';
@endphp
<span class="shrink-0 w-5 h-5 {{ $active ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}">
    <x-dynamic-component :component="'phosphor-' . $phIcon . ($active ? '-fill' : '')" class="w-5 h-5" />
</span>
