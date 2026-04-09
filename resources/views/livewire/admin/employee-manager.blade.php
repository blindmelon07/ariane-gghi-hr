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
    <div class="flex flex-wrap gap-3 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or code..." class="rounded-lg border-gray-300 dark:border-gray-600 text-sm w-64" />
        <select wire:model.live="filterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="">All Departments</option>
            @foreach ($this->departments as $dept)
                <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div wire:loading.delay class="px-6 py-2 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 text-xs font-medium">Loading...</div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->employees as $emp)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-6 py-4 text-sm font-mono text-gray-700 dark:text-gray-200">{{ $emp->emp_code }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $emp->full_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $emp->department ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $emp->position ?? '—' }}</td>
                        <td class="px-6 py-4">
                            @if ($emp->user)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $emp->user->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $emp->user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    {{ ucfirst($emp->user->role) }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 Account</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $emp->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-800' : 'bg-red-100 dark:bg-red-900/30 text-red-800' }}">
                                {{ $emp->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openAccountModal({{ $emp->id }})" class="text-sm font-medium {{ $emp->user ? 'text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:text-gray-100' : 'text-green-600 dark:text-green-400 hover:text-green-800' }}">
                                    {{ $emp->user ? 'Account' : 'Create Account' }}
                                </button>
                                <button wire:click="openEdit({{ $emp->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->employees->links() }}</div>

    {{-- Edit Modal --}}
    @if ($showEdit)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data x-on:keydown.escape.window="$wire.cancelEdit()">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6" @click.outside="$wire.cancelEdit()">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Edit Employee</h3>
            <form wire:submit="saveEmployee" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">First Name</label>
                        <input type="text" wire:model="editFirstName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('editFirstName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Last Name</label>
                        <input type="text" wire:model="editLastName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('editLastName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Department</label>
                        <input type="text" wire:model="editDepartment" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Position</label>
                        <input type="text" wire:model="editPosition" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date of Birth</label>
                        <input type="date" wire:model="editDob" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="editIsActive" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400" />
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60" x-data @click.self="$wire.closeAccountModal()">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">
                {{ $hasExistingAccount ? 'Manage Account' : 'Create Account' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ $accountName }} <span class="font-mono text-gray-400 dark:text-gray-500">({{ $accountEmpCode }})</span></p>

            @if ($hasExistingAccount)
                {{-- Existing account management --}}
                <div class="space-y-4">
                    {{-- Role --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Role</label>
                        <div class="flex gap-2">
                            <select wire:model="accountRole" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                                <option value="employee">Employee</option>
                                <option value="hr_admin">HR Admin</option>
                                <option value="manager">Manager</option>
                            </select>
                            <button wire:click="updateRole" class="px-3 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">Update</button>
                        </div>
                    </div>

                    {{-- Reset Password --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">New Password</label>
                        <div class="flex gap-2">
                            <input wire:model="accountPassword" type="text" placeholder="Min 6 characters" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                            <button wire:click="resetPassword" class="px-3 py-2 bg-amber-600 dark:bg-amber-500 text-white text-sm rounded-lg hover:bg-amber-700 dark:hover:bg-amber-600 transition">Reset</button>
                        </div>
                        @error('accountPassword') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Toggle Active --}}
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="toggleAccountActive" wire:confirm="Are you sure?" class="w-full px-4 py-2 text-sm font-medium rounded-lg transition
                            {{ $accountRole === 'hr_admin' ? 'bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:bg-red-900/30' : 'bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:bg-red-900/30' }}">
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
                        <input wire:model="accountPassword" type="text" placeholder="Set initial password (min 6 chars)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('accountPassword') <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Role</label>
                        <select wire:model="accountRole" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                            <option value="employee">Employee</option>
                            <option value="hr_admin">HR Admin</option>
                            <option value="manager">Manager</option>
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
