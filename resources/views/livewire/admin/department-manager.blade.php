<div class="space-y-4">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <x-phosphor-check-bold class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-2 px-4 py-3 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
            <x-phosphor-warning-circle-bold class="w-4 h-4 shrink-0" />
            {{ session('error') }}
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative">
            <x-phosphor-magnifying-glass-bold class="absolute w-4 h-4 -translate-y-1/2 left-3 top-1/2 text-gray-400" />
            <input aria-label="Search departments" wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="Search departments…"
                   class="pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition-colors">
            <x-phosphor-plus-bold class="w-4 h-4" />
            New Department
        </button>
    </div>

    {{-- Cards (mobile) --}}
    <div class="lg:hidden space-y-3">
        @forelse ($this->departments as $dept)
            <div wire:key="dept-m-{{ $dept->id }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $dept->name }}</p>
                        @if ($dept->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $dept->description }}</p>
                        @endif
                    </div>
                    @if ($dept->is_active)
                        <span class="shrink-0 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                        </span>
                    @else
                        <span class="shrink-0 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                        </span>
                    @endif
                </div>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Code: {{ $dept->code ?? '—' }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ $dept->employees_count }} employee{{ $dept->employees_count !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="openEdit({{ $dept->id }})" class="tbl-action">Edit</button>
                    <button wire:click="confirmDelete({{ $dept->id }})" class="tbl-action tbl-action-danger">Delete</button>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-sm text-center text-gray-400">
                No departments found. Create one to get started.
            </div>
        @endforelse

        @if ($this->departments->hasPages())
            <div class="pt-1">{{ $this->departments->links() }}</div>
        @endif
    </div>

    {{-- Table (desktop / tablet) --}}
    <div class="hidden lg:block overflow-hidden bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Department</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Code</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Employees</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($this->departments as $dept)
                    <tr wire:key="dept-{{ $dept->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $dept->name }}</p>
                            @if ($dept->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate max-w-xs">{{ $dept->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $dept->code ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                {{ $dept->employees_count }} employee{{ $dept->employees_count !== 1 ? 's' : '' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($dept->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openEdit({{ $dept->id }})"
                                        class="tbl-action px-2"
                                        title="Edit" aria-label="Edit {{ $dept->name }}">
                                    <x-phosphor-pencil-simple-bold class="w-4 h-4" />
                                </button>
                                <button wire:click="confirmDelete({{ $dept->id }})"
                                        class="tbl-action tbl-action-danger px-2"
                                        title="Delete" aria-label="Delete {{ $dept->name }}">
                                    <x-phosphor-trash-bold class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-sm text-center text-gray-400">
                            No departments found. Create one to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->departments->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->departments->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md p-6 mx-4 bg-white rounded-2xl shadow-xl dark:bg-gray-800">
                <h3 class="mb-5 text-base font-semibold text-gray-900 dark:text-white">
                    {{ $editId ? 'Edit Department' : 'New Department' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                        <input id="name" wire:model="name" type="text" placeholder="e.g. Human Resources"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="code" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                        <input id="code" wire:model="code" type="text" placeholder="e.g. HR"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea id="description" wire:model="description" rows="2" placeholder="Optional description…"
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input wire:model="isActive" type="checkbox" id="isActive"
                               class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <label for="isActive" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('showModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button wire:click="save"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-60">
                        {{ $editId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm modal --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-sm p-6 mx-4 bg-white rounded-2xl shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30">
                    <x-phosphor-warning-bold class="w-6 h-6 text-red-600" />
                </div>
                <h3 class="mb-2 text-base font-semibold text-center text-gray-900 dark:text-white">Delete Department</h3>
                <p class="mb-5 text-sm text-center text-gray-500 dark:text-gray-400">
                    This will permanently delete the department. Departments with assigned employees cannot be deleted.
                </p>
                <div class="flex gap-3">
                    <button wire:click="$set('showDeleteConfirm', false)"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button wire:click="delete"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-red-600 hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
