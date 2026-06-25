<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-green-700 dark:text-green-300 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Employee Management</h3>
        </div>
        <button wire:click="syncBioTime" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
            <span wire:loading.remove wire:target="syncBioTime">Import from BioTime</span>
            <span wire:loading wire:target="syncBioTime">Syncing...</span>
        </button>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 gap-2 mb-4 sm:flex sm:flex-wrap sm:gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or code..."
               class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
        <select wire:model.live="filterDept" class="w-full sm:w-auto rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="">All Departments</option>
            @foreach ($this->departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterStatus" class="w-full sm:w-auto rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div wire:loading.delay class="mb-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 text-xs font-medium rounded-lg">Loading...</div>

    {{-- ── MOBILE: Card layout (hidden on lg+) ── --}}
    <div class="lg:hidden space-y-3">
        @forelse ($this->employees as $emp)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-3">
                    {{-- Avatar + Name --}}
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                {{ strtoupper(substr($emp->first_name, 0, 1)) }}{{ strtoupper(substr($emp->last_name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $emp->full_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $emp->emp_code }}</p>
                        </div>
                    </div>
                    {{-- Status --}}
                    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $emp->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $emp->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                {{-- Meta info --}}
                <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Dept</span>
                        <p class="text-gray-700 dark:text-gray-300 font-medium truncate">{{ $emp->department ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Position</span>
                        <p class="text-gray-700 dark:text-gray-300 font-medium truncate">{{ $emp->position ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Type</span>
                        <p>
                            @if (($emp->employment_type ?? 'regular') === 'probationary')
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Probationary</span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Regular</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Account</span>
                        <p>
                            @if ($emp->user)
                                <span class="inline-flex items-center gap-1 rounded text-[10px] font-medium
                                    {{ $emp->user->is_active ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $emp->user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    {{ ucfirst($emp->user->role) }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">No account</span>
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex gap-2">
                    <button wire:click="openAccountModal({{ $emp->id }})"
                            class="flex-1 py-2 text-xs font-medium rounded-lg text-center transition
                                   {{ $emp->user ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' }}">
                        {{ $emp->user ? 'Account' : 'Create Account' }}
                    </button>
                    <button wire:click="openEdit({{ $emp->id }})"
                            class="flex-1 py-2 text-xs font-medium rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 transition">
                        Edit
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-400 dark:text-gray-500 text-sm">No employees found.</div>
        @endforelse
    </div>

    {{-- ── DESKTOP: Table layout (hidden on mobile) ── --}}
    <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->employees as $emp)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 text-sm font-mono text-gray-700 dark:text-gray-200">{{ $emp->emp_code }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $emp->full_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $emp->department ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $emp->position ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if (($emp->employment_type ?? 'regular') === 'probationary')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Probationary</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Regular</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($emp->user)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $emp->user->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $emp->user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        {{ ucfirst($emp->user->role) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $emp->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}">
                                    {{ $emp->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openAccountModal({{ $emp->id }})" class="text-sm font-medium {{ $emp->user ? 'text-gray-600 dark:text-gray-300 hover:text-gray-800' : 'text-green-600 dark:text-green-400 hover:text-green-800' }}">
                                        {{ $emp->user ? 'Account' : 'Create Account' }}
                                    </button>
                                    <button wire:click="openEdit({{ $emp->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $this->employees->links() }}</div>

    {{-- Edit Modal --}}
    @if ($showEdit)
    <div wire:click.self="cancelEdit" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 dark:bg-black/60" x-data x-on:keydown.escape.window="$wire.cancelEdit()">
        <div class="bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-xl shadow-xl w-full sm:max-w-lg p-6 max-h-[90dvh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Edit Employee</h3>
            <form wire:submit="saveEmployee" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">First Name</label>
                        <input type="text" wire:model.live="editFirstName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('editFirstName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Last Name</label>
                        <input type="text" wire:model.live="editLastName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('editLastName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Cell Number
                        <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">for SMS notifications</span>
                    </label>
                    <input type="text" wire:model.live="editCellNumber" placeholder="e.g. 09171234567"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('editCellNumber') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Department</label>
                        <select wire:model.live="editDepartmentId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                            <option value="">— No Department —</option>
                            @foreach ($this->departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Position</label>
                        <select wire:model.live="editPositionId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                            <option value="">— No Position —</option>
                            @foreach ($this->positions as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- Employment Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Employment Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors
                                      {{ $editEmploymentType === 'regular' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="editEmploymentType" value="regular" class="sr-only" />
                            <div>
                                <p class="text-sm font-semibold {{ $editEmploymentType === 'regular' ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200' }}">Regular</p>
                                <p class="text-xs {{ $editEmploymentType === 'regular' ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">2 days off / week</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors
                                      {{ $editEmploymentType === 'probationary' ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="editEmploymentType" value="probationary" class="sr-only" />
                            <div>
                                <p class="text-sm font-semibold {{ $editEmploymentType === 'probationary' ? 'text-amber-700 dark:text-amber-300' : 'text-gray-700 dark:text-gray-200' }}">Probationary</p>
                                <p class="text-xs {{ $editEmploymentType === 'probationary' ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">Sunday off only</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Weekday Off (Regular only) --}}
                @if ($editEmploymentType === 'regular')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        Weekly Rest Day
                        <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">(Sunday is always off)</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors
                                      {{ $editWeekdayOff == 6 ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="editWeekdayOff" value="6" class="sr-only" />
                            <div>
                                <p class="text-sm font-semibold {{ $editWeekdayOff == 6 ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200' }}">Saturday</p>
                                <p class="text-xs {{ $editWeekdayOff == 6 ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">Mon – Fri work week</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors
                                      {{ $editWeekdayOff == 1 ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="editWeekdayOff" value="1" class="sr-only" />
                            <div>
                                <p class="text-sm font-semibold {{ $editWeekdayOff == 1 ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200' }}">Monday</p>
                                <p class="text-xs {{ $editWeekdayOff == 1 ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">Tue – Sat work week</p>
                            </div>
                        </label>
                    </div>
                    @error('editWeekdayOff') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date of Birth</label>
                        <input type="date" wire:model.live="editDob" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="editIsActive" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="cancelEdit" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600">
                        <span wire:loading.remove wire:target="saveEmployee">Save Changes</span>
                        <span wire:loading wire:target="saveEmployee">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Account Management Modal --}}
    @if ($showAccountModal)
    <div wire:click.self="closeAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">
                {{ $hasExistingAccount ? 'Manage Account' : 'Create Account' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ $accountName }} <span class="font-mono text-gray-400 dark:text-gray-500">({{ $accountEmpCode }})</span></p>

            @if ($hasExistingAccount)
                {{-- Existing account management --}}
                <div class="space-y-4">

                    {{-- Change Username --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Change Username (Login Code)</p>
                        <div class="flex gap-2">
                            <input wire:model.live="accountNewUsername" type="text"
                                placeholder="New employee code"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm" />
                            <button wire:click="changeUsername"
                                wire:confirm="This will change the login code. The employee must use the new code to sign in. Proceed?"
                                class="px-3 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition whitespace-nowrap">
                                Update
                            </button>
                        </div>
                        @error('accountNewUsername') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">Current: <span class="font-mono font-medium">{{ $accountNewUsername }}</span></p>
                    </div>

                    {{-- Change Password --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Change Password</p>
                        <div class="flex gap-2">
                            <input wire:model.live="accountPassword" type="password"
                                placeholder="New password (min 6 chars)"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm" />
                            <button wire:click="resetPassword"
                                class="px-3 py-2 bg-amber-600 dark:bg-amber-500 text-white text-sm rounded-lg hover:bg-amber-700 dark:hover:bg-amber-600 transition whitespace-nowrap">
                                Reset
                            </button>
                        </div>
                        @error('accountPassword') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Role --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Role</p>
                        <div class="flex gap-2">
                            <select wire:model.live="accountRole" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                <option value="employee">Employee</option>
                                <option value="hr_admin">HR Admin</option>
                                <option value="manager">Manager</option>
                                <option value="approver">Approver</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                            <button wire:click="updateRole" class="px-3 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">Update</button>
                        </div>
                    </div>

                    {{-- Deactivate --}}
                    <div class="pt-1">
                        <button wire:click="toggleAccountActive" wire:confirm="Are you sure you want to deactivate this account?" class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                            Deactivate Account
                        </button>
                    </div>
                </div>
            @else
                {{-- Create new account --}}
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Login credentials</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Employee Code: <span class="font-mono">{{ $accountEmpCode }}</span></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Password</label>
                        <input wire:model.live="accountPassword" type="text" placeholder="Set initial password (min 6 chars)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('accountPassword') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Role</label>
                        <select wire:model.live="accountRole" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                            <option value="employee">Employee</option>
                            <option value="hr_admin">HR Admin</option>
                            <option value="manager">Manager</option>
                            <option value="approver">Approver</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>

                    <button wire:click="createAccount" class="w-full px-4 py-2 bg-green-600 dark:bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition">
                        Create Account
                    </button>
                </div>
            @endif

            <div class="flex justify-end mt-4">
                <button wire:click="closeAccountModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Close</button>
            </div>
        </div>
    </div>
    @endif
</div>
