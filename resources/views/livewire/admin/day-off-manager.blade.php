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
            <p class="text-sm text-gray-500 dark:text-gray-400">Assign rest days, holidays, and special day offs to employees.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openImport" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <x-phosphor-microsoft-excel-logo-bold class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                Import Excel
            </button>
            <button wire:click="openGenerate" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 dark:bg-emerald-500 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition">
                <x-phosphor-arrows-clockwise-bold class="w-4 h-4" />
                Auto-generate
            </button>
            <button wire:click="openBulk" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                <x-phosphor-users-three-bold class="w-4 h-4" />
                Bulk Assign
            </button>
            <button wire:click="openAdd" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                <x-phosphor-plus-bold class="w-4 h-4" />
                Add Day Off
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                <input id="search" wire:model.live.debounce.300ms="search" type="text" placeholder="Name or code…"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
            </div>
            <div>
                <label for="filterDept" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select id="filterDept" wire:model.live="filterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filterType" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                <select id="filterType" wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    <option value="rest_day">Rest Day</option>
                    <option value="holiday">Holiday</option>
                    <option value="special">Special</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label for="filterMonth" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Month</label>
                <input id="filterMonth" wire:model.live="filterMonth" type="month" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
            </div>
        </div>
    </div>

    {{-- Cards (mobile) --}}
    <div class="lg:hidden space-y-3">
        @forelse ($this->dayOffs as $off)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-medium truncate">{{ $off->employee->full_name ?? '' }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $off->employee->emp_code ?? '' }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-medium">{{ $off->date->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $off->date->format('l') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-3">
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
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">Recurring</span>
                @endif
                @if ($off->description)
                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $off->description }}</span>
                @endif
            </div>
            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <button wire:click="openEdit({{ $off->id }})" class="tbl-action">Edit</button>
                <button wire:click="delete({{ $off->id }})" wire:confirm="Remove this day off?" class="tbl-action tbl-action-danger">Delete</button>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 text-center text-gray-400 dark:text-gray-500 text-sm">
            No day offs found for this period.
        </div>
        @endforelse

        @if ($this->dayOffs->hasPages())
        <div class="pt-1">{{ $this->dayOffs->links() }}</div>
        @endif
    </div>

    {{-- Table (desktop / tablet) --}}
    <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-2.5">
                            <p class="font-medium">{{ $off->employee->full_name ?? '' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $off->employee->emp_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-2.5">{{ $off->date->format('M d, Y') }}</td>
                        <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $off->date->format('l') }}</td>
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
                        <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $off->description ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="openEdit({{ $off->id }})" class="tbl-action">Edit</button>
                                <button wire:click="delete({{ $off->id }})" wire:confirm="Remove this day off?" class="tbl-action tbl-action-danger">Delete</button>
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
                    <label for="empSearch" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        {{ $editId ? 'Employee' : 'Employees' }}
                    </label>
                    <input id="empSearch" wire:model.live.debounce.300ms="empSearch" @focus="open = true" @input="open = true" type="text" placeholder="Search employee…"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" {{ $editId ? 'disabled' : '' }} />

                    @if (!$editId)
                        {{-- Selected employee chips --}}
                        @if ($this->selectedEmployees->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach ($this->selectedEmployees as $emp)
                            <span class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium">
                                {{ $emp->full_name }}
                                <button type="button" wire:click="removeEmployee({{ $emp->id }})"
                                    class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-800 leading-none">&times;</button>
                            </span>
                            @endforeach
                        </div>
                        @endif

                        <div x-show="open && $wire.empSearch.length >= 2" class="relative">
                            <ul class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                                @foreach ($this->employeeResults as $emp)
                                <li wire:click="selectEmployee({{ $emp->id }})"
                                    class="px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 cursor-pointer text-sm">
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
                    @error('selectedEmployeeIds') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Mode toggle (only for new) --}}
                @if (!$editId)
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Assignment Mode</label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('mode', 'single')"
                            class="flex-1 py-2 px-3 text-sm font-medium rounded-lg border transition
                            {{ $mode === 'single' ? 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                            Single Date
                        </button>
                        <button type="button" wire:click="$set('mode', 'recurring')"
                            class="flex-1 py-2 px-3 text-sm font-medium rounded-lg border transition
                            {{ $mode === 'recurring' ? 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                            Recurring Weekly
                        </button>
                    </div>
                </div>
                @endif

                @if ($mode === 'single' || $editId)
                {{-- Single date --}}
                <div>
                    <label for="date" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date</label>
                    <input id="date" wire:model.live="date" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('date') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                @if ($editId && $date)
                {{-- Day of week (edit only): shifts the date above to the nearest matching weekday --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Day of Week</label>
                    <div class="flex flex-wrap gap-2">
                        @php $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; $currentDow = \Illuminate\Support\Carbon::parse($date)->dayOfWeek; @endphp
                        @foreach ($dayLabels as $i => $label)
                        <button type="button" wire:click="setEditDayOfWeek({{ $i }})"
                            class="px-3 py-2 rounded-lg border text-sm font-medium transition
                            {{ $currentDow === $i ? 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">Shifts the date above to the nearest matching weekday.</p>
                </div>
                @endif
                @else
                {{-- Recurring: day checkboxes --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Days of Week</label>
                    <div class="flex flex-wrap gap-2">
                        @php $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                        @foreach ($dayLabels as $i => $label)
                        <label class="flex items-center gap-1.5 px-3 py-2 rounded-lg border cursor-pointer text-sm transition
                            {{ in_array($i, $selectedDays) ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                            <input type="checkbox" wire:model.live="selectedDays" value="{{ $i }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 w-3.5 h-3.5" />
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('selectedDays') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Validity date range --}}
                <div>
                    <label for="dateFrom" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Validity Period</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <input id="dateFrom" wire:model.live="dateFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">From</p>
                            @error('dateFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input aria-label="To date" wire:model.live="dateTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
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
                    <label for="type" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                    <select id="type" wire:model.live="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="rest_day">Rest Day</option>
                        <option value="holiday">Holiday</option>
                        <option value="special">Special</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description (optional)</label>
                    <input id="description" wire:model.live="description" type="text" placeholder="e.g. Weekly rest day, New Year's Day"
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
                    <label for="bulkDept" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                    <select id="bulkDept" wire:model.live="bulkDept" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
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
                            {{ in_array($i, $bulkSelectedDays) ? 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-400 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                            <input type="checkbox" wire:model.live="bulkSelectedDays" value="{{ $i }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 w-3.5 h-3.5" />
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('bulkSelectedDays') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Date Range --}}
                <div>
                    <label for="bulkDateFrom" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Validity Period</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <input id="bulkDateFrom" wire:model.live="bulkDateFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">From</p>
                            @error('bulkDateFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input aria-label="To date" wire:model.live="bulkDateTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
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
                    <label for="bulkType" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                    <select id="bulkType" wire:model.live="bulkType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="rest_day">Rest Day</option>
                        <option value="holiday">Holiday</option>
                        <option value="special">Special</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                {{-- Description --}}
                <div>
                    <label for="bulkDescription" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description (optional)</label>
                    <input id="bulkDescription" wire:model.live="bulkDescription" type="text" placeholder="e.g. Weekly rest day"
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

    {{-- Import Excel (Schedule Day Off grid) Modal --}}
    @if ($showImportModal)
    @php
        $dayNames     = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $importRows   = collect($importEntries);
        $importOk     = $importRows->whereNull('problem');
        $importIssues = $importRows->whereNotNull('problem');
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showImportModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full {{ $importEntries ? 'max-w-3xl' : 'max-w-lg' }} mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Import Day-Off Schedule</h3>

            @if (! $importEntries)
                {{-- Step 1: upload --}}
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Upload the <strong>Schedule Day Off</strong> sheet: weekdays across the top, employee names under the day they are off.
                    You'll review the matches before anything is saved.
                </p>

                <a href="{{ asset('samples/schedule-day-off-sample.xlsx') }}" download
                   class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                    <x-phosphor-download-simple-bold class="w-4 h-4" />
                    Download sample sheet
                </a>

                <div>
                    <label for="importFile" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Excel file (.xlsx, .xls, .csv)</label>
                    <input id="importFile" type="file" wire:model="importFile" accept=".xlsx,.xls,.csv"
                        class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/40 dark:file:text-indigo-300 hover:file:bg-indigo-100" />
                    <p wire:loading wire:target="importFile" class="text-xs text-gray-400 dark:text-gray-500 mt-1">Uploading…</p>
                    @error('importFile') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('showImportModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                    <button wire:click="readImportFile" wire:loading.attr="disabled" wire:target="importFile,readImportFile"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="readImportFile">Read File</span>
                        <span wire:loading wire:target="readImportFile">Reading…</span>
                    </button>
                </div>
            @else
                {{-- Step 2: review + apply --}}
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $importOk->count() }} matched</span>
                    @if ($importIssues->isNotEmpty())
                        · <span class="font-medium text-red-600 dark:text-red-400">{{ $importIssues->count() }} will be skipped</span>
                    @endif
                    — each matched employee's weekly rest day is set to the day shown, and rest days are created for the period below.
                </p>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label for="importDateFrom" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Effective from</label>
                        <input id="importDateFrom" wire:model.live="importDateFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('importDateFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="importDateTo" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Create rest days until</label>
                        <input id="importDateTo" wire:model.live="importDateTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('importDateTo') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 uppercase text-xs">
                            <tr>
                                <th class="px-3 py-2">Name in sheet</th>
                                <th class="px-3 py-2">Section</th>
                                <th class="px-3 py-2">Day off</th>
                                <th class="px-3 py-2">Matched employee</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($importIssues->concat($importOk) as $row)
                            <tr class="{{ $row['problem'] ? 'bg-red-50/60 dark:bg-red-950/20' : '' }}">
                                <td class="px-3 py-2 font-medium">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row['section'] ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $dayNames[$row['weekday']] }}</td>
                                <td class="px-3 py-2">
                                    @if ($row['employee_id'])
                                        {{ $row['employee_name'] }}
                                        <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $row['emp_code'] }}</span>
                                    @endif
                                    @if ($row['problem'])
                                        <span class="block text-xs font-medium text-red-600 dark:text-red-400">{{ $row['problem'] }} — skipped</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($importIssues->isNotEmpty())
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        To include skipped names, fix the spelling in the sheet (or the employee record) and import again.
                    </p>
                @endif

                <div class="flex justify-between gap-3 mt-6">
                    <button wire:click="openImport" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Choose another file</button>
                    <button wire:click="applyImport" wire:loading.attr="disabled" wire:target="applyImport" @disabled($importOk->isEmpty())
                        wire:confirm="Apply this schedule to {{ $importOk->count() }} employee(s)? Their weekly rest day will change and rest days will be regenerated for the selected period."
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 dark:bg-emerald-500 rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="applyImport">Apply Schedule</span>
                        <span wire:loading wire:target="applyImport">Applying…</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Auto-generate by Schedule Modal --}}
    @if ($showGenerateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" wire:click.self="$set('showGenerateModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Auto-generate Standard Day Offs</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                Applies each employee's schedule to the selected period:
            </p>

            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 p-3 mb-5 space-y-1.5 text-sm">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Regular (Sat off)</span>
                    <span class="text-gray-600 dark:text-gray-300">Saturday + Sunday</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Regular (Mon off)</span>
                    <span class="text-gray-600 dark:text-gray-300">Monday + Sunday</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Probationary</span>
                    <span class="text-gray-600 dark:text-gray-300">Sunday only</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="genDateFrom" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                    <input id="genDateFrom" wire:model.live="genDateFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('genDateFrom') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="genDateTo" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                    <input id="genDateTo" wire:model.live="genDateTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('genDateTo') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button wire:click="$set('showGenerateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="generateStandard" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 dark:bg-emerald-500 rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition">
                    <span wire:loading.remove wire:target="generateStandard">Generate</span>
                    <span wire:loading wire:target="generateStandard">Generating...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
