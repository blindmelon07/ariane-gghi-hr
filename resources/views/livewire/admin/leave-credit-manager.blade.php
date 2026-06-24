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

        {{-- Monthly Accrual Status --}}
        <div class="mb-5 p-4 rounded-xl border {{ $this->thisMonthAccrued ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        @if ($this->thisMonthAccrued)
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-semibold {{ $this->thisMonthAccrued ? 'text-green-800 dark:text-green-300' : 'text-amber-800 dark:text-amber-300' }}">
                            Monthly Accrual — {{ now()->format('F Y') }}
                        </p>
                        @if ($this->lastAccrual)
                            <p class="text-xs mt-0.5 {{ $this->thisMonthAccrued ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                @if ($this->thisMonthAccrued)
                                    ✓ This month already processed — {{ $this->lastAccrual->employee_count }} employees received
                                    +{{ $this->lastAccrual->accrual_days }}d VL &amp; SL
                                    <span class="opacity-60">({{ \Carbon\Carbon::parse($this->lastAccrual->updated_at)->format('M d, Y g:i A') }}
                                    · {{ $this->lastAccrual->triggered_by }})</span>
                                @else
                                    Last run: {{ \Carbon\Carbon::createFromDate($this->lastAccrual->year, $this->lastAccrual->month, 1)->format('F Y') }}
                                    · Next auto-run: {{ now()->startOfMonth()->addMonth()->format('M 1, Y') }} at 6:00 AM
                                @endif
                            </p>
                        @else
                            <p class="text-xs mt-0.5 text-amber-600 dark:text-amber-400">No accrual has been run yet. Click "Run Now" to credit this month.</p>
                        @endif
                    </div>
                </div>
                <button wire:click="accrueThisMonth"
                        wire:confirm="This will add 2.5 VL + 2.5 SL to all active employees for {{ now()->format('F Y') }}. Continue?"
                        wire:loading.attr="disabled"
                        wire:target="accrueThisMonth"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg transition
                               {{ $this->thisMonthAccrued ? 'bg-gray-500 hover:bg-gray-600' : 'bg-green-600 hover:bg-green-700' }}
                               disabled:opacity-60 shrink-0">
                    <svg wire:loading wire:target="accrueThisMonth" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg wire:loading.remove wire:target="accrueThisMonth" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ $this->thisMonthAccrued ? 'Re-run Accrual' : 'Run Now' }}
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
                            <input wire:model.live="modalCredits.{{ $type->id }}.total" type="number" step="0.5" min="0"
                                class="w-16 rounded border-gray-300 dark:border-gray-600 text-xs text-center shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 dark:text-gray-500 text-center">Used</label>
                            <input wire:model.live="modalCredits.{{ $type->id }}.used" type="number" step="0.5" min="0"
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
                    <select wire:model.live="bulkLeaveTypeId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select…</option>
                        @foreach ($this->leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                        @endforeach
                    </select>
                    @error('bulkLeaveTypeId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Credits</label>
                    <input wire:model.live="bulkCredits" type="number" step="0.5" min="0" placeholder="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('bulkCredits') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Mode</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="bulkMode" type="radio" value="set" class="text-indigo-600 dark:text-indigo-400" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">Set total to value</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="bulkMode" type="radio" value="add" class="text-indigo-600 dark:text-indigo-400" />
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
