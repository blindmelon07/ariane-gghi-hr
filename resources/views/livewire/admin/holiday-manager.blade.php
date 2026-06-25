<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Holiday Management</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Company-wide holidays automatically apply to all employees in attendance.</p>
        </div>
        <button wire:click="openAdd"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Holiday
        </button>
    </div>

    {{-- Year filter --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
        <div class="flex items-center gap-3">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Year</label>
            <select wire:model.live="filterYear" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Holiday</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Recurring</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->holidays as $holiday)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-100">
                                {{ $holiday->date->format('M d, Y') }}
                                <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">({{ $holiday->date->format('l') }})</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-100 font-medium">{{ $holiday->name }}</td>
                            <td class="px-6 py-4">
                                @php
                                    [$badgeClass, $label] = match($holiday->type) {
                                        'regular'             => ['bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'Regular Holiday'],
                                        'special_non_working' => ['bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300', 'Special Non-Working'],
                                        'special_working'     => ['bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'Special Working'],
                                        default               => ['bg-gray-100 text-gray-700', $holiday->type],
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $label }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($holiday->is_recurring)
                                    <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Every year
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">One-time</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button wire:click="openEdit({{ $holiday->id }})"
                                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</button>
                                    <button wire:click="delete({{ $holiday->id }})"
                                            wire:confirm="Remove this holiday?"
                                            class="text-sm text-red-500 dark:text-red-400 hover:text-red-700 font-medium">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                                No holidays for {{ $filterYear }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $this->holidays->links() }}</div>

    {{-- Add / Edit Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
         wire:click.self="$set('showModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-5">
                {{ $editId ? 'Edit Holiday' : 'Add Holiday' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date</label>
                    <input type="date" wire:model.live="date"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Holiday Name</label>
                    <input type="text" wire:model.live="name" placeholder="e.g. Christmas Day"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('name') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Type</label>
                    <select wire:model.live="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="regular">Regular Holiday (paid, no work)</option>
                        <option value="special_non_working">Special Non-Working (paid, no work)</option>
                        <option value="special_working">Special Working (premium pay if worked)</option>
                    </select>
                </div>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                    <input type="checkbox" wire:model.live="is_recurring"
                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600" />
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Recurring every year</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Automatically applies on the same date each year (e.g. Christmas, New Year)</p>
                    </div>
                </label>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button wire:click="save"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 transition">
                    <span wire:loading.remove wire:target="save">{{ $editId ? 'Update' : 'Add Holiday' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
