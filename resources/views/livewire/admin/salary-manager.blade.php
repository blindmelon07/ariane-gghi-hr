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

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50">
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
                            <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                <div>{{ $employee->full_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 $employee->emp_code }}</div>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $employee->department }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $employee->position }}</td>
                            <td class="px-6 py-3">
                                <input type="number" step="0.01" wire:model.live.debounce.500ms="basicSalary"
                                       class="w-32 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                                @error('basicSalary') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-6 py-3">
                                <input type="number" step="0.01" wire:model="dailyRate"
                                       class="w-28 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                            </td>
                            <td class="px-6 py-3">
                                <input type="number" step="0.01" wire:model="hourlyRate"
                                       class="w-28 rounded-lg border-gray-300 dark:border-gray-600 text-sm text-right" placeholder="0.00" />
                            </td>
                            <td class="px-6 py-3 text-right space-x-2">
                                <button wire:click="save" class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 font-medium">Save</button>
                                <button wire:click="cancelEdit" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200 font-medium">Cancel</button>
                            </td>
                        </tr>
                    @else
                        {{-- Display Row --}}
                        <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                <div>{{ $employee->full_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 $employee->emp_code }}</div>
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
