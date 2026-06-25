<div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Fleet — Drivers</h3>
            <button wire:click="openCreate"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                + Add Driver
            </button>
        </div>

        <div class="mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by employee name or code..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 w-full sm:w-72">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">License No.</th>
                        <th class="px-4 py-3">License Expiry</th>
                        <th class="px-4 py-3">Medical Clearance</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->drivers as $d)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $d->employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $d->employee->emp_code }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-300">{{ $d->license_number }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                @if ($d->license_expiry)
                                    @php $expired = $d->license_expiry->isPast(); @endphp
                                    <span class="{{ $expired ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                                        {{ $d->license_expiry->format('M d, Y') }}
                                    </span>
                                    @if ($expired) <span class="text-red-500 text-[10px]">(Expired)</span> @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                @if ($d->medical_clearance_date)
                                    {{ $d->medical_clearance_date->format('M d, Y') }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="toggleActive({{ $d->id }})"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                               {{ $d->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ $d->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="openEdit({{ $d->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No drivers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->drivers->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                {{ $editingId ? 'Edit Driver' : 'Add Driver' }}
            </h4>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Employee *</label>
                    <select wire:model.live="employee_id" {{ $editingId ? 'disabled' : '' }}
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 disabled:opacity-60">
                        <option value="">— Select employee —</option>
                        @foreach ($this->employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->emp_code }})</option>
                        @endforeach
                        @if ($editingId)
                            @php $curEmp = \App\Models\Driver::find($editingId)?->employee; @endphp
                            @if ($curEmp)
                                <option value="{{ $curEmp->id }}" selected>{{ $curEmp->full_name }} ({{ $curEmp->emp_code }})</option>
                            @endif
                        @endif
                    </select>
                    @error('employee_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">License Number *</label>
                    <input type="text" wire:model.live="license_number" placeholder="A01-23-456789"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('license_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">License Expiry</label>
                    <input type="date" wire:model.live="license_expiry"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('license_expiry') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Medical Clearance Date
                        <span class="text-xs text-gray-400 font-normal">(quarterly check-up)</span>
                    </label>
                    <input type="date" wire:model.live="medical_clearance_date"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    @error('medical_clearance_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model.live="is_active" id="driver_active" class="rounded border-gray-300">
                    <label for="driver_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button wire:click="save"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    {{ $editingId ? 'Update' : 'Add Driver' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
