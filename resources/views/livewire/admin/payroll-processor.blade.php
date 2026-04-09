<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-green-700 dark:text-green-300 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-950/30 p-4 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Payroll Periods</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 $this->employeeCount }} employees with salary configured</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                <option value="all">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="processing">Processing</option>
                <option value="processed">Processed</option>
                <option value="finalized">Finalized</option>
            </select>
            <button wire:click="$set('showCreate', true)" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Period
            </button>
        </div>
    </div>

    {{-- Create Period Modal --}}
    @if ($showCreate)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data x-on:keydown.escape.window="$wire.set('showCreate', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6" @click.outside="$wire.set('showCreate', false)">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Create Payroll Period</h3>
            <form wire:submit="createPeriod" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Period Name</label>
                    <input type="text" wire:model="periodName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('periodName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Cutoff Type</label>
                    <select wire:model="cutoffType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="semi_monthly_1">1st Half (1-15)</option>
                        <option value="semi_monthly_2">2nd Half (16-end)</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Start Date</label>
                        <input type="date" wire:model="startDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('startDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">End Date</label>
                        <input type="date" wire:model="endDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('endDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showCreate', false)" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600">Create</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Periods Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cutoff</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dates</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payslips</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->periods as $period)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $period->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', ucfirst($period->cutoff_type)) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $period->start_date->format('M d') }} – {{ $period->end_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = ['draft' => 'gray', 'processing' => 'yellow', 'processed' => 'blue', 'finalized' => 'green'];
                                $c = $colors[$period->status] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800">
                                {{ ucfirst($period->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $period->payslips()->count() }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @if ($period->status === 'draft' || $period->status === 'processed')
                                <button wire:click="generatePayslips({{ $period->id }})" wire:confirm="Generate payslips for all employees?" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">
                                    {{ $period->status === 'draft' ? 'Generate' : 'Regenerate' }}
                                </button>
                            @endif
                            @if ($period->status === 'processed')
                                <button wire:click="finalize({{ $period->id }})" wire:confirm="Finalize this payroll? This cannot be undone." class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 font-medium">Finalize</button>
                            @endif
                            @if (in_array($period->status, ['processed', 'finalized']))
                                <button wire:click="exportExcel({{ $period->id }})" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:text-gray-100 font-medium">Excel</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No payroll periods found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->periods->links() }}
    </div>
</div>
