<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-green-700 dark:text-green-300 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Search --}}
    <div class="mb-6">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Search by name or employee code..."
               class="w-full max-w-md rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm" />
    </div>

    {{-- Cards (mobile) --}}
    <div class="lg:hidden space-y-3">
        @forelse ($this->employees as $employee)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $employee->full_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $employee->emp_code }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $employee->department }} · {{ $employee->position }}</p>
                    </div>
                    @if ($editingId !== $employee->id)
                        <button wire:click="edit({{ $employee->id }})" class="shrink-0 text-sm text-indigo-600 dark:text-indigo-400 font-medium">Edit</button>
                    @endif
                </div>

                @if ($editingId === $employee->id)
                    {{-- Inline edit --}}
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-3">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Salary Rates</p>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <label class="w-24 text-xs text-gray-600 dark:text-gray-400 shrink-0">Basic Salary</label>
                                    <input type="number" step="0.01" wire:model="basicSalary"
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                    <button wire:click="autoComputeRates" title="Auto-compute daily & hourly"
                                            class="shrink-0 px-2 py-1 rounded bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 text-xs font-bold hover:bg-indigo-200">↻</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="w-24 text-xs text-gray-600 dark:text-gray-400 shrink-0">Daily Rate</label>
                                    <input type="number" step="0.01" wire:model="dailyRate"
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="w-24 text-xs text-gray-600 dark:text-gray-400 shrink-0">Hourly Rate</label>
                                    <input type="number" step="0.01" wire:model="hourlyRate"
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Allowances <span class="text-gray-400 font-normal">(semi-monthly)</span></p>
                            <div class="space-y-2">
                                @foreach ([
                                    'hazardPay'          => 'Hazard Pay',
                                    'riceAllowance'      => 'Rice Allowance',
                                    'medicalAllowance'   => 'Laundry/Medical',
                                    'commodityAllowance' => 'Commodity',
                                    'otherAllowance'     => 'Other Allowance',
                                ] as $field => $label)
                                <div class="flex items-center gap-2">
                                    <label class="w-24 text-xs text-gray-600 dark:text-gray-400 shrink-0">{{ $label }}</label>
                                    <input type="number" step="0.01" wire:model="{{ $field }}"
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="save" class="flex-1 px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Save</button>
                            <button wire:click="cancelEdit" class="flex-1 px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-3 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs">
                        <div>
                            <p class="text-gray-400 dark:text-gray-500">Basic Salary</p>
                            <p class="font-mono text-gray-900 dark:text-gray-100">{{ $employee->salaryDetail ? '₱' . number_format($employee->salaryDetail->basic_salary, 2) : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 dark:text-gray-500">Daily Rate</p>
                            <p class="font-mono text-gray-600 dark:text-gray-300">{{ $employee->salaryDetail ? '₱' . number_format($employee->salaryDetail->daily_rate, 2) : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 dark:text-gray-500">Hourly Rate</p>
                            <p class="font-mono text-gray-600 dark:text-gray-300">{{ $employee->salaryDetail ? '₱' . number_format($employee->salaryDetail->hourly_rate, 2) : '—' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 text-center text-gray-400 dark:text-gray-500 text-sm">No active employees found.</div>
        @endforelse
    </div>

    {{-- Table (desktop / tablet) --}}
    <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Position</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Basic Salary</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daily Rate</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hourly Rate</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->employees as $employee)
                    @if ($editingId === $employee->id)
                        {{-- Inline Edit Row --}}
                        <tr class="bg-indigo-50 dark:bg-indigo-950/30">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100" colspan="7">
                                <div class="font-semibold mb-3">{{ $employee->full_name }} <span class="font-mono text-gray-500 text-xs">({{ $employee->emp_code }})</span></div>

                                <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                                    {{-- Left: Rate fields --}}
                                    <div class="space-y-2">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Salary Rates</p>
                                        <div class="flex items-center gap-2">
                                            <label class="w-28 text-xs text-gray-600 dark:text-gray-400">Basic Salary</label>
                                            <input type="number" step="0.01" wire:model="basicSalary"
                                                   class="w-32 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                            <button wire:click="autoComputeRates" title="Auto-compute daily & hourly"
                                                    class="px-2 py-1 rounded bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 text-xs font-bold hover:bg-indigo-200">↻ Auto</button>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label class="w-28 text-xs text-gray-600 dark:text-gray-400">Daily Rate</label>
                                            <input type="number" step="0.01" wire:model="dailyRate"
                                                   class="w-32 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label class="w-28 text-xs text-gray-600 dark:text-gray-400">Hourly Rate <span class="text-amber-500">(deductions)</span></label>
                                            <input type="number" step="0.01" wire:model="hourlyRate"
                                                   class="w-32 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                        </div>
                                    </div>

                                    {{-- Right: Allowances --}}
                                    <div class="space-y-2">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Allowances <span class="text-gray-400 font-normal">(semi-monthly)</span></p>
                                        @foreach ([
                                            'hazardPay'          => 'Hazard Pay',
                                            'riceAllowance'      => 'Rice Allowance',
                                            'medicalAllowance'   => 'Laundry/Medical',
                                            'commodityAllowance' => 'Commodity',
                                            'otherAllowance'     => 'Other Allowance',
                                        ] as $field => $label)
                                        <div class="flex items-center gap-2">
                                            <label class="w-28 text-xs text-gray-600 dark:text-gray-400">{{ $label }}</label>
                                            <input type="number" step="0.01" wire:model="{{ $field }}"
                                                   class="w-32 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <button wire:click="save" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Save</button>
                                    <button wire:click="cancelEdit" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                                </div>
                            </td>
                        </tr>
                    @else
                        {{-- Display Row --}}
                        <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                <div>{{ $employee->full_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $employee->emp_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $employee->department }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $employee->position }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100 font-mono">
                                {{ $employee->salaryDetail ? '₱ ' . number_format($employee->salaryDetail->basic_salary, 2) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-600 dark:text-gray-300 font-mono">
                                {{ $employee->salaryDetail ? '₱ ' . number_format($employee->salaryDetail->daily_rate, 2) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-600 dark:text-gray-300 font-mono">
                                {{ $employee->salaryDetail ? '₱ ' . number_format($employee->salaryDetail->hourly_rate, 2) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="edit({{ $employee->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</button>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No active employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->employees->links() }}
    </div>
</div>
