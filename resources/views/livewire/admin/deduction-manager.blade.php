<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Other Deductions</h3>
            <p class="text-sm text-gray-500">Manage recurring and one-time deductions per employee.</p>
        </div>
        <button wire:click="openAdd" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Deduction
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search Employee</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Name, code, or description…"
                    class="w-full rounded-lg border-gray-300 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                <select wire:model.live="filterDept" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Per Cutoff</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($this->deductions as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5">
                            <p class="font-medium">{{ $d->employee->full_name ?? '' }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ $d->employee->emp_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-2.5">{{ $d->description }}</td>
                        <td class="px-4 py-2.5 text-right font-mono">{{ number_format($d->amount_per_cutoff, 2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono">{{ number_format($d->remaining_balance, 2) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $d->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $d->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="openEdit({{ $d->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</button>
                                <button wire:click="toggleActive({{ $d->id }})" class="text-xs font-medium {{ $d->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $d->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No deductions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->deductions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $this->deductions->links() }}
        </div>
        @endif
    </div>

    {{-- Add / Edit Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-data @click.self="$wire.set('showModal', false)">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $editDeductionId ? 'Edit' : 'Add' }} Deduction</h3>

            <div class="space-y-4">
                {{-- Employee search --}}
                <div x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
                    <input wire:model.live.debounce.300ms="empSearch" @focus="open = true" @input="open = true" type="text" placeholder="Search employee…"
                        class="w-full rounded-lg border-gray-300 text-sm" {{ $editDeductionId ? 'disabled' : '' }} />
                    @if (!$editDeductionId)
                    <div x-show="open && $wire.empSearch.length >= 2" class="relative">
                        <ul class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                            @foreach ($this->employeeResults as $emp)
                            <li wire:click="selectEmployee({{ $emp->id }})" @click="open = false"
                                class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm">
                                {{ $emp->full_name }} <span class="text-gray-400">({{ $emp->emp_code }})</span>
                            </li>
                            @endforeach
                            @if ($this->employeeResults->isEmpty())
                            <li class="px-3 py-2 text-gray-400 text-sm">No results</li>
                            @endif
                        </ul>
                    </div>
                    @endif
                    @error('modalEmployeeId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <input wire:model="description" type="text" placeholder="e.g. Cash Advance, Uniform, Loan"
                        class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Amount per cutoff --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Amount per Cutoff</label>
                    <input wire:model="amountPerCutoff" type="number" step="0.01" min="0" placeholder="0.00"
                        class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('amountPerCutoff') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Remaining balance --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Remaining Balance</label>
                    <input wire:model="remainingBalance" type="number" step="0.01" min="0" placeholder="0.00"
                        class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('remainingBalance') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Active toggle --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="isActive" type="checkbox" class="rounded border-gray-300 text-indigo-600" />
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Cancel</button>
                <button wire:click="save" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    {{ $editDeductionId ? 'Update' : 'Create' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
