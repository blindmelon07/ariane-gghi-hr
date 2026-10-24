{{--
    "More" (⋯) menu for table rows that have more actions than fit inline.
    Items go in the slot as <button class="tbl-menu-item" role="menuitem" …>.
    The panel is teleported to <body> and anchored to the trigger, so a table's
    overflow-x-auto wrapper can't clip it.
--}}
@props(['label' => 'More actions'])

<div x-data="{ open: false }" class="relative inline-flex" @keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }">
    <button type="button" x-ref="trigger"
            @click="open = !open; if (open) $nextTick(() => $refs.panel.querySelector('[role=menuitem]')?.focus())"
            :aria-expanded="open.toString()" aria-haspopup="menu"
            aria-label="{{ $label }}" title="{{ $label }}"
            {{ $attributes->merge(['class' => 'tbl-action px-2']) }}>
        <x-phosphor-dots-three-bold class="w-4 h-4" />
    </button>

    <template x-teleport="body">
        <div x-ref="panel" x-show="open" x-anchor.bottom-end.offset.4="$refs.trigger"
             @click.outside="if (! $refs.trigger.contains($event.target)) open = false"
             @click="if ($event.target.closest('[role=menuitem]')) open = false"
             x-transition.opacity.duration.100ms
             role="menu" aria-label="{{ $label }}" style="display: none;"
             class="z-[60] min-w-[11rem] overflow-hidden rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/10 dark:bg-gray-800 dark:ring-white/10">
            {{ $slot }}
        </div>
    </template>
</div>
