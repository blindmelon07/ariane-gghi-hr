<div class="space-y-6">

    {{-- Flash --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <x-phosphor-check-bold class="w-4 h-4 shrink-0" />
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
                        <x-phosphor-plus-bold class="w-3 h-3" />
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
                                    <x-phosphor-trash-bold class="w-3.5 h-3.5" />
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
                        <x-phosphor-plus-bold class="w-3 h-3" />
                        New Permission
                    </button>
                </div>

                @if (! $selectedRole)
                    <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                        <x-phosphor-shield-check class="w-10 h-10 mb-3 opacity-40" />
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
                                    <x-phosphor-circle-notch wire:loading wire:target="saveRolePermissions" class="w-4 h-4 animate-spin" />
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
                <input aria-label="New role name" wire:model="newRoleName"
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
                <input aria-label="New permission name" wire:model="newPermissionName"
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
                    <x-phosphor-warning-bold class="w-6 h-6 text-red-600" />
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
