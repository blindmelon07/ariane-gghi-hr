<div class="space-y-6">
    {{-- Flash Message --}}
    @if (session('message'))
        <div class="bg-green-50 dark:bg-green-950/30 border border-green-300 dark:border-green-600 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Schedule Management</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage shift schedules and assign them to employees per department.</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex gap-4 -mb-px">
            <button wire:click="$set('tab', 'templates')"
                class="pb-3 px-1 text-sm font-medium border-b-2 transition
                {{ $tab === 'templates' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200' }}">
                Schedule Templates
            </button>
            <button wire:click="$set('tab', 'assignments')"
                class="pb-3 px-1 text-sm font-medium border-b-2 transition
                {{ $tab === 'assignments' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200' }}">
                Employee Assignments
            </button>
        </nav>
    </div>

    {{-- ==================== TEMPLATES TAB ==================== --}}
    @if ($tab === 'templates')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <input wire:model.live.debounce.300ms="searchSched" type="text" placeholder="Search schedule…"
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                <select wire:model.live="filterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm w-48">
                    <option value="">All Departments</option>
                    @foreach ($this->scheduleDepartments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
                <button wire:click="openAddSchedule" class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition whitespace-nowrap">
                    + Add Schedule
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Department</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Time In - Out</th>
                        <th class="px-4 py-3 text-center">Break</th>
                        <th class="px-4 py-3 text-center">Split</th>
                        <th class="px-4 py-3 text-center">Night</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->schedules as $sched)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-200">{{ $sched->department }}</td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $sched->name }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-gray-700 dark:text-gray-200">{{ substr($sched->time_in, 0, 5) }} - {{ substr($sched->time_out, 0, 5) }}</span>
                            @if ($sched->time_in_2)
                                <br><span class="font-mono text-gray-500 dark:text-gray-400 text-xs">{{ substr($sched->time_in_2, 0, 5) }} - {{ substr($sched->time_out_2, 0, 5) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($sched->break_start)
                                <span class="text-xs text-gray-500 dark:text-gray-400 substr($sched->break_start, 0, 5) }}-{{ substr($sched->break_end, 0, 5) }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($sched->time_in_2)
                                <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Split</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($sched->is_night_shift)
                                <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">Night</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleScheduleActive({{ $sched->id }})"
                                class="inline-block px-2 py-0.5 text-xs rounded-full cursor-pointer transition
                                {{ $sched->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 hover:bg-green-200 dark:bg-green-800/30' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:bg-red-800/30' }}">
                                {{ $sched->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <button wire:click="openEditSchedule({{ $sched->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">Edit</button>
                                <button wire:click="deleteSchedule({{ $sched->id }})" wire:confirm="Delete this schedule?" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:text-red-300 text-xs font-medium ml-2">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No schedules found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            {{ $this->schedules->links() }}
        </div>
    </div>
    @endif

    {{-- ==================== ASSIGNMENTS TAB ==================== --}}
    @if ($tab === 'assignments')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <input wire:model.live.debounce.300ms="assignSearch" type="text" placeholder="Search employee…"
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                <select wire:model.live="assignFilterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm w-48">
                    <option value="">All Departments</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
                <button wire:click="openAssign" class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition whitespace-nowrap">
                    + Assign Schedule
                </button>
                <button wire:click="openBulk" class="px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition whitespace-nowrap">
                    Bulk Assign
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Employee</th>
                        <th class="px-4 py-3 text-left">Department</th>
                        <th class="px-4 py-3 text-left">Schedule</th>
                        <th class="px-4 py-3 text-left">Time</th>
                        <th class="px-4 py-3 text-center">Effective From</th>
                        <th class="px-4 py-3 text-center">Effective To</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->assignments as $assign)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $assign->employee->first_name }} {{ $assign->employee->last_name }}</span>
                            <br><span class="text-xs text-gray-400 dark:text-gray-500">{{ $assign->employee->emp_code }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $assign->employee->department }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300">
                                {{ $assign->schedule->name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-200 text-xs">
                            {{ $assign->schedule->formatted_time }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $assign->effective_from->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">
                            {{ $assign->effective_to ? $assign->effective_to->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <button wire:click="openEditAssign({{ $assign->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-xs font-medium">Edit</button>
                                <button wire:click="deleteAssign({{ $assign->id }})" wire:confirm="Remove this assignment?" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:text-red-300 text-xs font-medium ml-2">Remove</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No schedule assignments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            {{ $this->assignments->links() }}
        </div>
    </div>
    @endif

    {{-- ==================== ADD/EDIT SCHEDULE MODAL ==================== --}}
    @if ($showSchedModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showSchedModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">{{ $editSchedId ? 'Edit' : 'Add' }} Schedule</h3>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Schedule Name</label>
                        <input wire:model="schedName" type="text" placeholder="e.g. Nursing Day 12h" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('schedName') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                        <input wire:model="schedDept" type="text" list="dept-list" placeholder="e.g. Nursing" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        <datalist id="dept-list">
                            @foreach ($this->scheduleDepartments as $d)
                                <option value="{{ $d }}">
                            @endforeach
                        </datalist>
                        @error('schedDept') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Time In</label>
                        <input wire:model="schedTimeIn" type="time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('schedTimeIn') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Time Out</label>
                        <input wire:model="schedTimeOut" type="time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('schedTimeOut') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Break Start <span class="text-gray-400 dark:text-gray-500">(optional)</span></label>
                        <input wire:model="schedBreakStart" type="time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Break End <span class="text-gray-400 dark:text-gray-500">(optional)</span></label>
                        <input wire:model="schedBreakEnd" type="time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Split Shift (2nd period, optional)</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Time In 2</label>
                            <input wire:model="schedTimeIn2" type="time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Time Out 2</label>
                            <input wire:model="schedTimeOut2" type="time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="schedNightShift" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400" />
                        <span class="text-sm text-gray-700 dark:text-gray-200">Night Shift</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description <span class="text-gray-400 dark:text-gray-500">(optional)</span></label>
                    <input wire:model="schedDescription" type="text" placeholder="e.g. 12:00-13:00 Lunch Break" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showSchedModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="saveSchedule" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    {{ $editSchedId ? 'Update' : 'Create' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== ASSIGN SCHEDULE MODAL ==================== --}}
    @if ($showAssignModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showAssignModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">{{ $editAssignId ? 'Edit' : 'Assign' }} Schedule</h3>

            <div class="space-y-4">
                {{-- Employee search --}}
                <div x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Employee</label>
                    <input wire:model.live.debounce.300ms="assignEmpSearch" @focus="open = true" @input="open = true" type="text" placeholder="Search employee…"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" {{ $editAssignId ? 'disabled' : '' }} />
                    @if (!$editAssignId)
                    <div x-show="open && $wire.assignEmpSearch.length >= 2" class="relative">
                        <ul class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                            @foreach ($this->employeeResults as $emp)
                            <li wire:click="selectEmployee({{ $emp->id }})" @click="open = false"
                                class="px-3 py-2 hover:bg-indigo-50 dark:bg-indigo-950/30 cursor-pointer text-sm">
                                {{ $emp->first_name }} {{ $emp->last_name }} <span class="text-gray-400 dark:text-gray-500">({{ $emp->emp_code }})</span>
                            </li>
                            @endforeach
                            @if ($this->employeeResults->isEmpty())
                            <li class="px-3 py-2 text-gray-400 dark:text-gray-500 text-sm">No results</li>
                            @endif
                        </ul>
                    </div>
                    @endif
                    @error('assignEmployeeId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Schedule picker --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Schedule</label>
                    <select wire:model="assignScheduleId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select schedule…</option>
                        @foreach ($this->allSchedules->groupBy('department') as $dept => $scheds)
                            <optgroup label="{{ $dept }}">
                                @foreach ($scheds as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->formatted_time }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('assignScheduleId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Date range --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Effective From</label>
                        <input wire:model="assignFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('assignFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Effective To <span class="text-gray-400 dark:text-gray-500">(optional)</span></label>
                        <input wire:model="assignTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showAssignModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="saveAssign" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    {{ $editAssignId ? 'Update' : 'Assign' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== BULK ASSIGN MODAL ==================== --}}
    @if ($showBulkModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.set('showBulkModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Bulk Assign Schedule</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Assign a schedule to all active employees in a department.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                    <select wire:model.live="bulkDept" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select department…</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                    @error('bulkDept') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Schedule</label>
                    <select wire:model="bulkScheduleId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select schedule…</option>
                        @foreach ($this->deptSchedules as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->formatted_time }})</option>
                        @endforeach
                    </select>
                    @error('bulkScheduleId') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Effective From</label>
                        <input wire:model="bulkFrom" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('bulkFrom') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Effective To <span class="text-gray-400 dark:text-gray-500">(optional)</span></label>
                        <input wire:model="bulkTo" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showBulkModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Cancel</button>
                <button wire:click="bulkAssign" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    Assign to Department
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
