<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Leave Credit Manager ({{ $year }})</h3>

            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="year" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                    @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>

                <button wire:click="openBulkAdd"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 dark:bg-green-500 text-white text-xs font-medium rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Bulk Add Credits
                </button>

                <button wire:click="resetCreditsForYear"
                        wire:confirm="This will reset all credits for {{ $year }}. Continue?"
                        class="inline-flex items-center px-3 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    Reset All Credits
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-end gap-4 mb-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search Employee</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Name or code…"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select wire:model.live="filterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        @foreach ($this->leaveTypes as $type)
                            <th class="px-4 py-3 text-center" title="{{ $type->name }}">{{ $type->code }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($this->employees as $employee)
                        <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $employee->emp_code }} · {{ $employee->department ?? '—' }}</div>
                            </td>
                            @foreach ($this->leaveTypes as $type)
                                @php
                                    $credit = $employee->leaveCredits->firstWhere('leave_type_id', $type->id);
                                @endphp
                                <td class="px-4 py-3 text-center">
                                    @if ($credit)
                                        <span class="text-gray-700 dark:text-gray-200 font-medium">{{ number_format($credit->remaining_credits, 1) }}</span>
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">/ {{ number_format($credit->total_credits, 1) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center">
                                <button wire:click="openEmployeeCredits({{ $employee->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->employees->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $this->employees->links() }}
        </div>
        @endif
    </div>

    {{-- Per-Employee Modal --}}
    @if ($showEmployeeModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showEmployeeModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Edit Leave Credits</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $modalEmployeeName }} &mdash; {{ $year }}</p>

            <div class="space-y-3">
                @foreach ($this->leaveTypes as $type)
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $type->name }} ({{ $type->code }})</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div>
                            <label class="block text-[10px] text-gray-400 dark:text-gray-500 text-center">Total</label>
                            <input wire:model="modalCredits.{{ $type->id }}.total" type="number" step="0.5" min="0"
                                class="w-16 rounded border-gray-300 dark:border-gray-600 text-xs text-center shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 dark:text-gray-500 text-center">Used</label>
                            <input wire:model="modalCredits.{{ $type->id }}.used" type="number" step="0.5" min="0"
                                class="w-16 rounded border-gray-300 dark:border-gray-600 text-xs text-center shadow-sm" />
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @error('modalCredits.*.*') <p class="text-xs text-red-500 dark:text-red-400 mt-2">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showEmployeeModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="saveEmployeeCredits" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">Save Credits</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk Add Modal --}}
    @if ($showBulkModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showBulkModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">Bulk Add Credits</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Apply to <strong>all active employees</strong> for {{ $year }}.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Leave Type</label>
                    <select wire:model="bulkLeaveTypeId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select…</option>
                        @foreach ($this->leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                        @endforeach
                    </select>
                    @error('bulkLeaveTypeId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Credits</label>
                    <input wire:model="bulkCredits" type="number" step="0.5" min="0" placeholder="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('bulkCredits') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Mode</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="bulkMode" type="radio" value="set" class="text-indigo-600 dark:text-indigo-400" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">Set total to value</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="bulkMode" type="radio" value="add" class="text-indigo-600 dark:text-indigo-400" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">Add to existing</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showBulkModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="saveBulk" class="px-4 py-2 text-sm font-medium text-white bg-green-600 dark:bg-green-500 rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition">Apply to All</button>
            </div>
        </div>
    </div>
    @endif
</div>
