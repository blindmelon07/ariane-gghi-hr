<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Deductions</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 deduction types and assign them to employees.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="openAddType" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Manage Types
            </button>
            <button wire:click="openAdd" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Deduction
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Name, code, or description…"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                <select wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All Types</option>
                    @foreach ($this->deductionTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
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
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Per Cutoff</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->deductions as $d)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-4 py-2.5">
                            <p class="font-medium">{{ $d->employee->full_name ?? '' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $d->employee->emp_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-2.5">
                            @if ($d->deductionType)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ match($d->deductionType->category) {
                                        'government' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                        'loan' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                                        'benefit' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                                        default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                                    } }}">
                                    {{ $d->deductionType->code }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">{{ $d->description }}</td>
                        <td class="px-4 py-2.5 text-right font-mono">{{ number_format($d->amount_per_cutoff, 2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono">{{ number_format($d->remaining_balance, 2) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $d->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 }}">
                                {{ $d->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="openEdit({{ $d->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">Edit</button>
                                <button wire:click="toggleActive({{ $d->id }})" class="text-xs font-medium {{ $d->is_active ? 'text-red-600 dark:text-red-400 hover:text-red-800' : 'text-green-600 dark:text-green-400 hover:text-green-800' }}">
                                    {{ $d->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No deductions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->deductions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $this->deductions->links() }}
        </div>
        @endif
    </div>

    {{-- Add / Edit Deduction Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">{{ $editDeductionId ? 'Edit' : 'Add' }} Deduction</h3>

            <div class="space-y-4">
                {{-- Employee search --}}
                <div x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Employee</label>
                    <input wire:model.live.debounce.300ms="empSearch" @focus="open = true" @input="open = true" type="text" placeholder="Search employee…"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" {{ $editDeductionId ? 'disabled' : '' }} />
                    @if (!$editDeductionId)
                    <div x-show="open && $wire.empSearch.length >= 2" class="relative">
                        <ul class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                            @foreach ($this->employeeResults as $emp)
                            <li wire:click="selectEmployee({{ $emp->id }})" @click="open = false"
                                class="px-3 py-2 hover:bg-indigo-50 dark:bg-indigo-950/30 cursor-pointer text-sm">
                                {{ $emp->full_name }} <span class="text-gray-400 dark:text-gray-500">({{ $emp->emp_code }})</span>
                            </li>
                            @endforeach
                            @if ($this->employeeResults->isEmpty())
                            <li class="px-3 py-2 text-gray-400 dark:text-gray-500 text-sm">No results</li>
                            @endif
                        </ul>
                    </div>
                    @endif
                    @error('modalEmployeeId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Deduction Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Deduction Type</label>
                    <select wire:model.live="deductionTypeId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select type…</option>
                        @php $lastCat = ''; @endphp
                        @foreach ($this->deductionTypes as $type)
                            @if ($type->category !== $lastCat)
                                @if ($lastCat) </optgroup> @endif
                                <optgroup label="{{ ucfirst($type->category) }}">
                                @php $lastCat = $type->category; @endphp
                            @endif
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                        @endforeach
                        @if ($lastCat) </optgroup> @endif
                    </select>
                    @error('deductionTypeId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description / Notes</label>
                    <input wire:model="description" type="text" placeholder="Auto-filled from type, or custom note"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('description') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Amount per cutoff --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Amount per Cutoff</label>
                    <input wire:model="amountPerCutoff" type="number" step="0.01" min="0" placeholder="0.00"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('amountPerCutoff') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Remaining balance --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Remaining Balance</label>
                    <input wire:model="remainingBalance" type="number" step="0.01" min="0" placeholder="0.00"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">For loans: total remaining. For recurring (PhilHealth, Pag-IBIG): set to 0.</p>
                    @error('remainingBalance') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Active toggle --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="isActive" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400" />
                    <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="save" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    {{ $editDeductionId ? 'Update' : 'Create' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Manage Deduction Types Modal --}}
    @if ($showTypeModal && !$editTypeId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showTypeModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Deduction Types</h3>
                <button wire:click="$set('editTypeId', -1)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Type
                </button>
            </div>

            @php $categories = ['government' => 'Government', 'loan' => 'Loans', 'benefit' => 'Benefits', 'other' => 'Other']; @endphp
            @foreach ($categories as $catKey => $catLabel)
                @php $catTypes = $this->allDeductionTypes->where('category', $catKey); @endphp
                @if ($catTypes->isNotEmpty())
                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">{{ $catLabel }}</h4>
                    <div class="space-y-1">
                        @foreach ($catTypes as $t)
                        <div class="flex items-center justify-between px-3 py-2 rounded-lg {{ $t->is_active ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-red-50 dark:bg-red-950/30/50' }}">
                            <div>
                                <span class="text-sm font-medium {{ $t->is_active ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500 line-through' }}">{{ $t->name }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">{{ $t->code }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="openEditType({{ $t->id }})" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</button>
                                <button wire:click="toggleTypeActive({{ $t->id }})" class="text-xs font-medium {{ $t->is_active ? 'text-red-500 dark:text-red-400 hover:text-red-700 dark:text-red-300' : 'text-green-600 dark:text-green-400 hover:text-green-800' }}">
                                    {{ $t->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            <div class="flex justify-end mt-4">
                <button wire:click="$set('showTypeModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Close</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Add / Edit Deduction Type Form Modal --}}
    @if ($showTypeModal && $editTypeId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showTypeModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">{{ $editTypeId === -1 ? 'New' : 'Edit' }} Deduction Type</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Code</label>
                    <input wire:model="typeCode" type="text" placeholder="e.g. SSS_LOAN" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm uppercase" maxlength="30" />
                    @error('typeCode') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Name</label>
                    <input wire:model="typeName" type="text" placeholder="e.g. SSS Salary Loan" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('typeName') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Category</label>
                    <select wire:model="typeCategory" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="government">Government</option>
                        <option value="loan">Loan</option>
                        <option value="benefit">Benefit</option>
                        <option value="other">Other</option>
                    </select>
                    @error('typeCategory') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('editTypeId', null)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Back</button>
                <button wire:click="saveType" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    {{ $editTypeId === -1 ? 'Create' : 'Update' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
