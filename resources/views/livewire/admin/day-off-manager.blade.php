<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-green-700 dark:text-green-300 text-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Day Off Management</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 rest days, holidays, and special day offs to employees.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="openBulk" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                Bulk Assign
            </button>
            <button wire:click="openAdd" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Day Off
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
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
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                <select wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    <option value="rest_day">Rest Day</option>
                    <option value="holiday">Holiday</option>
                    <option value="special">Special</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Month</label>
                <input wire:model.live="filterMonth" type="month" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
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
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Day</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->dayOffs as $off)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-4 py-2.5">
                            <p class="font-medium">{{ $off->employee->full_name ?? '' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $off->employee->emp_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-2.5">{{ $off->date->format('M d, Y') }}</td>
                        <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400 $off->date->format('l') }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ match($off->type) {
                                    'rest_day' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                    'holiday'  => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                    'special'  => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                                    default    => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                                } }}">
                                {{ str_replace('_', ' ', ucfirst($off->type)) }}
                            </span>
                            @if ($off->is_recurring)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 ml-1">Recurring</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400 $off->description ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="openEdit({{ $off->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">Edit</button>
                                <button wire:click="delete({{ $off->id }})" wire:confirm="Remove this day off?" class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No day offs found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->dayOffs->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $this->dayOffs->links() }}
        </div>
        @endif
    </div>

    {{-- Add / Edit Day Off Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">{{ $editId ? 'Edit' : 'Add' }} Day Off</h3>

            <div class="space-y-4">
                {{-- Employee search --}}
                <div x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Employee</label>
                    <input wire:model.live.debounce.300ms="empSearch" @focus="open = true" @input="open = true" type="text" placeholder="Search employee…"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" {{ $editId ? 'disabled' : '' }} />
                    @if (!$editId)
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

                {{-- Mode toggle (only for new) --}}
                @if (!$editId)
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Assignment Mode</label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('mode', 'single')"
                            class="flex-1 py-2 px-3 text-sm font-medium rounded-lg border transition
                            {{ $mode === 'single' ? 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:bg-gray-800/50' }}">
                            Single Date
                        </button>
                        <button type="button" wire:click="$set('mode', 'recurring')"
                            class="flex-1 py-2 px-3 text-sm font-medium rounded-lg border transition
                            {{ $mode === 'recurring' ? 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:bg-gray-800/50' }}">
                            Recurring Weekly
                        </button>
                    </div>
                </div>
                @endif

                @if ($mode === 'single' || $editId)
                {{-- Single date --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date</label>
                    <input wire:model="date" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('date') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                @else
                {{-- Recurring: day checkboxes --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Days of Week</label>
                    <div class="flex flex-wrap gap-2">
                        @php $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                        @foreach ($dayLabels as $i => $label)
                        <label class="flex items-center gap-1.5 px-3 py-2 rounded-lg border cursor-pointer text-sm transition
                            {{ in_array($i, $selectedDays) ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50' }}">
                            <input type="checkbox" wire:model.live="selectedDays" value="{{ $i }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 w-3.5 h-3.5" />
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('selectedDays') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Validity date range --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Validity Period</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <input wire:model="dateFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">From</p>
                            @error('dateFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input wire:model="dateTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">To</p>
                            @error('dateTo') <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button type="button" wire:click="$set('dateTo', '{{ now()->addMonths(3)->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">3 months</button>
                        <button type="button" wire:click="$set('dateTo', '{{ now()->addMonths(6)->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">6 months</button>
                        <button type="button" wire:click="$set('dateTo', '{{ now()->endOfYear()->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">End of year</button>
                        <button type="button" wire:click="$set('dateTo', '{{ now()->addYear()->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">1 year</button>
                    </div>
                </div>
                @endif

                {{-- Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                    <select wire:model="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="rest_day">Rest Day</option>
                        <option value="holiday">Holiday</option>
                        <option value="special">Special</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description (optional)</label>
                    <input wire:model="description" type="text" placeholder="e.g. Weekly rest day, New Year's Day"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="save" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    {{ $editId ? 'Update' : ($mode === 'recurring' ? 'Generate Day Offs' : 'Create') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk Assign Modal --}}
    @if ($showBulkModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showBulkModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Bulk Assign Day Offs</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Assign recurring day offs to all active employees in a department.</p>

            <div class="space-y-4">
                {{-- Department --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                    <select wire:model="bulkDept" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select department…</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                    @error('bulkDept') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Days of Week checkboxes --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Days of Week</label>
                    <div class="flex flex-wrap gap-2">
                        @php $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                        @foreach ($dayLabels as $i => $label)
                        <label class="flex items-center gap-1.5 px-3 py-2 rounded-lg border cursor-pointer text-sm transition
                            {{ in_array($i, $bulkSelectedDays) ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50' }}">
                            <input type="checkbox" wire:model.live="bulkSelectedDays" value="{{ $i }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 w-3.5 h-3.5" />
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('bulkSelectedDays') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Date Range --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Validity Period</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <input wire:model="bulkDateFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">From</p>
                            @error('bulkDateFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input wire:model="bulkDateTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">To</p>
                            @error('bulkDateTo') <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button type="button" wire:click="$set('bulkDateTo', '{{ now()->addMonths(3)->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">3 months</button>
                        <button type="button" wire:click="$set('bulkDateTo', '{{ now()->addMonths(6)->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">6 months</button>
                        <button type="button" wire:click="$set('bulkDateTo', '{{ now()->endOfYear()->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">End of year</button>
                        <button type="button" wire:click="$set('bulkDateTo', '{{ now()->addYear()->format('Y-m-d') }}')"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">1 year</button>
                    </div>
                </div>

                {{-- Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                    <select wire:model="bulkType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="rest_day">Rest Day</option>
                        <option value="holiday">Holiday</option>
                        <option value="special">Special</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description (optional)</label>
                    <input wire:model="bulkDescription" type="text" placeholder="e.g. Weekly rest day"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showBulkModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="bulkAssign" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    Generate Day Offs
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
