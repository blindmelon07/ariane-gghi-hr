<div class="space-y-6">

    {{-- Flash --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ── Roles panel ── --}}
        <div class="lg:col-span-1">
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Roles</h3>
                    <button wire:click="$set('showNewRoleModal', true)"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Role
                    </button>
                </div>

                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($roles as $role)
                        <li wire:key="role-{{ $role->id }}"
                            wire:click="selectRole({{ $role->id }})"
                            class="flex items-center justify-between px-5 py-3 cursor-pointer transition-colors
                                   {{ $selectedRoleId === $role->id
                                       ? 'bg-blue-50 dark:bg-blue-900/20'
                                       : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <div>
                                <p class="text-sm font-medium {{ $selectedRoleId === $role->id ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $role->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $role->permissions_count }} permission{{ $role->permissions_count !== 1 ? 's' : '' }}
                                    &bull; {{ $role->users_count }} user{{ $role->users_count !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if ($selectedRoleId === $role->id)
                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                                        selected
                                    </span>
                                @endif
                                <button wire:click.stop="confirmDelete({{ $role->id }})"
                                        class="p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="Delete role">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-center text-gray-400">No roles found.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- ── Permissions panel ── --}}
        <div class="lg:col-span-2">
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        @if ($selectedRole)
                            Permissions — <span class="text-blue-600 dark:text-blue-400">{{ $selectedRole->name }}</span>
                        @else
                            Permissions
                        @endif
                    </h3>
                    <button wire:click="$set('showNewPermissionModal', true)"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Permission
                    </button>
                </div>

                @if (! $selectedRole)
                    <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                        <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                        <p class="text-sm">Select a role to manage its permissions</p>
                    </div>
                @else
                    <div class="p-5">
                        @if ($permissions->isEmpty())
                            <p class="text-sm text-center text-gray-400 py-8">No permissions defined yet. Create one using the button above.</p>
                        @else
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <label wire:key="perm-{{ $permission->id }}"
                                           class="flex items-center gap-3 px-4 py-3 border rounded-lg cursor-pointer transition-colors
                                                  {{ in_array((string)$permission->id, $rolePermissions)
                                                      ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20'
                                                      : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500' }}">
                                        <input type="checkbox"
                                               wire:model="rolePermissions"
                                               value="{{ $permission->id }}"
                                               class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="flex justify-end mt-5">
                                <button wire:click="saveRolePermissions"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-60 transition-colors">
                                    <svg wire:loading wire:target="saveRolePermissions" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Save Permissions
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── New Role modal ── --}}
    @if ($showNewRoleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md p-6 mx-4 bg-white rounded-2xl shadow-xl dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Create New Role</h3>
                <input wire:model="newRoleName"
                       type="text"
                       placeholder="e.g. department_head"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('newRoleName')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('showNewRoleModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button wire:click="createRole"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700">
                        Create
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── New Permission modal ── --}}
    @if ($showNewPermissionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md p-6 mx-4 bg-white rounded-2xl shadow-xl dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Create New Permission</h3>
                <input wire:model="newPermissionName"
                       type="text"
                       placeholder="e.g. view payroll"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('newPermissionName')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('showNewPermissionModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button wire:click="createPermission"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-indigo-600 hover:bg-indigo-700">
                        Create
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Delete confirm modal ── --}}
    @if ($confirmDeleteRole)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-sm p-6 mx-4 bg-white rounded-2xl shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <h3 class="mb-2 text-base font-semibold text-center text-gray-900 dark:text-white">Delete Role</h3>
                <p class="mb-5 text-sm text-center text-gray-500 dark:text-gray-400">
                    This will remove the role and revoke it from all assigned users. This cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button wire:click="$set('confirmDeleteRole', false)"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button wire:click="deleteRole"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-red-600 hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
