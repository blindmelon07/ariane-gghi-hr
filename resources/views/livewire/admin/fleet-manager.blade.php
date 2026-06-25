<div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Fleet — Vehicles</h3>
            <button wire:click="openCreate"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                + Add Vehicle
            </button>
        </div>

        {{-- Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search plate, make, model..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">

            <select wire:model.live="filterStatus"
                    class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                <option value="all">All Statuses</option>
                <option value="available">Available</option>
                <option value="in_use">In Use</option>
                <option value="under_maintenance">Under Maintenance</option>
            </select>

            <select wire:model.live="filterType"
                    class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                <option value="all">All Types</option>
                <option value="sedan">Sedan</option>
                <option value="van">Van</option>
                <option value="SUV">SUV</option>
                <option value="pickup">Pickup</option>
                <option value="ambulance">Ambulance</option>
                <option value="truck">Truck</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Plate</th>
                        <th class="px-4 py-3">Vehicle</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Capacity</th>
                        <th class="px-4 py-3">Year</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Active</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->vehicles as $v)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $v->plate_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $v->make }} {{ $v->model }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 capitalize">{{ $v->vehicle_type }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->capacity }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->year ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match($v->status) {
                                        'available'          => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'in_use'             => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'under_maintenance'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        default              => 'bg-gray-100 text-gray-500',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $badge }}">
                                    {{ str_replace('_', ' ', ucfirst($v->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="toggleActive({{ $v->id }})"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                               {{ $v->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ $v->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="openEdit({{ $v->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No vehicles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->vehicles->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if ($showModal)
    <div x-data x-init="$nextTick(() => {})"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-lg mx-4" @click.stop>
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                {{ $editingId ? 'Edit Vehicle' : 'Add Vehicle' }}
            </h4>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plate Number *</label>
                    <input type="text" wire:model.live="plate_number" placeholder="ABC-1234"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('plate_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                    <select wire:model.live="vehicle_type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                        <option value="sedan">Sedan</option>
                        <option value="van">Van</option>
                        <option value="SUV">SUV</option>
                        <option value="pickup">Pickup</option>
                        <option value="ambulance">Ambulance</option>
                        <option value="truck">Truck</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Make *</label>
                    <input type="text" wire:model.live="make" placeholder="Toyota"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('make') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model *</label>
                    <input type="text" wire:model.live="model" placeholder="Fortuner"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('model') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capacity (seats) *</label>
                    <input type="number" wire:model.live="capacity" min="1" max="60"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('capacity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                    <input type="number" wire:model.live="year" placeholder="2024" min="1990" max="2100"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                    <select wire:model.live="status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                        <option value="available">Available</option>
                        <option value="in_use">In Use</option>
                        <option value="under_maintenance">Under Maintenance</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" wire:model.live="is_active" id="vehicle_active" class="rounded border-gray-300">
                    <label for="vehicle_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button wire:click="save"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    {{ $editingId ? 'Update' : 'Add Vehicle' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
