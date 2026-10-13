<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-950/30 p-4 text-sm text-red-700 dark:text-red-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">User Accounts</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage system accounts and their roles.</p>
        </div>
        <button wire:click="openAdd"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
            <x-phosphor-plus-bold class="w-4 h-4" />
            Add Account
        </button>
    </div>

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Search by name or employee code..."
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
    </div>

    {{-- Cards (mobile) --}}
    <div class="lg:hidden space-y-3">
        @forelse ($this->users as $user)
            @php
                [$bg, $label] = match($user->role) {
                    'super_admin'    => ['bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300', 'Super Admin'],
                    'hr_admin'       => ['bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'HR Admin'],
                    'approver'       => ['bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300', 'Approver'],
                    'manager'        => ['bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300', 'Manager'],
                    'department_head'=> ['bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300', 'Dept. Head'],
                    'security_guard' => ['bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300', 'Security Guard'],
                    'head_nurse'     => ['bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300', 'Head Nurse'],
                    default          => ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300', 'Employee'],
                };
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-white">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $user->name }}</p>
                        <p class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $user->employee_code }}</p>
                        @unless ($user->employee)
                            <p class="text-xs text-yellow-600 dark:text-yellow-400">Not linked to an employee</p>
                        @endunless
                    </div>
                    @if ($user->is_active)
                        <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                        </span>
                    @else
                        <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-gray-400 dark:text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                        </span>
                    @endif
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bg }}">{{ $label }}</span>
                    <div class="flex items-center gap-3">
                        @if ($this->canManage($user))
<button wire:click="openEdit({{ $user->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">Edit</button>
                        <button wire:click="toggleActive({{ $user->id }})"
                                class="text-sm font-medium {{ $user->is_active ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        @if ($user->id !== auth()->id())
                            <button wire:click="delete({{ $user->id }})"
                                    wire:confirm="Delete account {{ $user->name }}? This cannot be undone."
                                    class="text-sm text-red-500 dark:text-red-400 font-medium">Delete</button>
                        @endif
@else
<span class="text-xs text-gray-400 dark:text-gray-500">Super admin only</span>
@endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center text-gray-400 dark:text-gray-500 text-sm">No accounts found.</div>
        @endforelse
    </div>

    {{-- Table (desktop / tablet) --}}
    <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Employee Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-bold text-white">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</span>
                                        @if ($user->employee)
                                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $user->employee->department ?: 'Employee record linked' }}</p>
                                        @else
                                            <p class="text-xs text-yellow-600 dark:text-yellow-400">Not linked to an employee</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $user->employee_code }}</td>
                            <td class="px-6 py-4">
                                @php
                                    [$bg, $label] = match($user->role) {
                                        'super_admin'    => ['bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300', 'Super Admin'],
                                        'hr_admin'       => ['bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'HR Admin'],
                                        'approver'       => ['bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300', 'Approver'],
                                        'manager'        => ['bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300', 'Manager'],
                                        'department_head'=> ['bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300', 'Dept. Head'],
                                        'security_guard' => ['bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300', 'Security Guard'],
                                        'head_nurse'     => ['bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300', 'Head Nurse'],
                                        default          => ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300', 'Employee'],
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bg }}">{{ $label }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 dark:text-gray-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($this->canManage($user))
<button wire:click="openEdit({{ $user->id }})"
                                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</button>
                                    <button wire:click="toggleActive({{ $user->id }})"
                                            class="text-sm font-medium {{ $user->is_active ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-800' : 'text-green-600 dark:text-green-400 hover:text-green-800' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    @if ($user->id !== auth()->id())
                                        <button wire:click="delete({{ $user->id }})"
                                                wire:confirm="Delete account {{ $user->name }}? This cannot be undone."
                                                class="text-sm text-red-500 dark:text-red-400 hover:text-red-700 font-medium">Delete</button>
                                    @endif
@else
<span class="text-xs text-gray-400 dark:text-gray-500">Super admin only</span>
@endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                                No accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $this->users->links() }}</div>

    {{-- Add / Edit Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
         wire:click.self="$set('showModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-5">
                {{ $editId ? 'Edit Account' : 'Add Account' }}
            </h3>

            <div class="space-y-4">
                @unless ($editId)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Employee</label>
                        @if ($this->pickedEmployee)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/30">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><span class="font-mono">{{ $employeeCode }}</span>{{ $this->pickedEmployee->department ? ' · ' . $this->pickedEmployee->department : '' }}</p>
                                </div>
                                <button type="button" wire:click="clearEmployee" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">Change</button>
                            </div>
                        @else
                            <input type="text" wire:model.live.debounce.300ms="employeeSearch" placeholder="Search employees without an account…"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($this->availableEmployees as $emp)
                                    <button type="button" wire:key="pick-{{ $emp->id }}" wire:click="pickEmployee({{ $emp->id }})"
                                            class="w-full flex items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <span class="text-gray-800 dark:text-gray-100 truncate">{{ $emp->full_name ?: trim($emp->first_name . ' ' . $emp->last_name) }}</span>
                                        <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500"><span class="font-mono">{{ $emp->emp_code }}</span>{{ $emp->department ? ' · ' . $emp->department : '' }}</span>
                                    </button>
                                @empty
                                    <p class="px-3 py-3 text-xs text-center text-gray-400 dark:text-gray-500">No employees without an account{{ $employeeSearch ? ' match your search' : '' }}.</p>
                                @endforelse
                            </div>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Pick an employee to fill in their name and code, or type them below for an account with no employee record.</p>
                        @endif
                    </div>
                @endunless

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Full Name</label>
                    <input type="text" wire:model.live="name" placeholder="e.g. Juan dela Cruz"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Employee Code</label>
                    <input type="text" wire:model.live="employeeCode" placeholder="e.g. EMP001"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                    @error('employeeCode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Role</label>
                    <select wire:model.live="role" @disabled($this->roleLocked)
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                        @foreach ($this->roleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($this->roleLocked)
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">You can't change your own role. Ask a super admin.</p>
                    @elseif ($role === 'super_admin')
                        <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Super Admin has full access, including approving any request at any step. Existing super admins are notified.</p>
                    @endif
                    @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Password {{ $editId ? '(leave blank to keep current)' : '' }}
                    </label>
                    <input type="password" wire:model.live="password"
                           placeholder="{{ $editId ? 'Enter new password to change' : 'Min. 6 characters' }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if ($editId)
                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                    <input type="checkbox" wire:model.live="isActive"
                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Active account</span>
                </label>
                @endif
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button wire:click="save"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 transition">
                    <span wire:loading.remove wire:target="save">{{ $editId ? 'Update' : 'Create Account' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
