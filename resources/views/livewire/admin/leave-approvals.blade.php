<div x-data="{ showModal: false }"
     @open-action-modal.window="showModal = true">

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">Leave Approvals</h3>

        {{-- Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">

            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All</option>
            </select>

            <select wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                <option value="">All Types</option>
                @foreach (\App\Models\LeaveType::all() as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                <option value="">All Departments</option>
                @foreach (\App\Models\Employee::distinct()->pluck('department')->filter() as $dept)
                    <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
            </select>
        </div>

        {{-- My approval step indicator --}}
        @php $myStep = $this->myStep; $steps = \App\Services\LeaveService::APPROVAL_STEPS; @endphp
        @if ($myStep)
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            You are <span class="font-semibold text-indigo-600 dark:text-indigo-400 mx-1">{{ $steps[$myStep]['label'] }}</span>
            — Step {{ $myStep }} of {{ count($steps) }} in the approval chain
        </div>
        @endif

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3">Days</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Filed</th>
                        <th class="px-4 py-3">Approval Progress</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->pendingRequests as $req)
                        @php
                            $approvedSteps = $req->approvals->where('action','approved')->pluck('step')->toArray();
                            $rejectedStep  = $req->approvals->where('action','rejected')->first()?->step;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $req->employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $req->employee->emp_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->leaveType->code }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs">
                                @if ($req->is_half_day)
                                    {{ $req->start_date->format('M d, Y') }}
                                @else
                                    {{ $req->start_date->format('M d') }} – {{ $req->end_date->format('M d, Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">{{ $req->total_days }}</span>
                                    @if ($req->is_half_day)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">½ day</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-[160px] truncate text-xs">{{ $req->reason }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500">{{ $req->created_at->diffForHumans() }}</td>

                            {{-- Approval progress chain --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    @foreach ($steps as $stepNum => $stepConfig)
                                        @php
                                            $isApproved = in_array($stepNum, $approvedSteps);
                                            $isRejected = $rejectedStep === $stepNum;
                                            $isCurrent  = $req->approval_step === $stepNum && $req->status === 'pending';
                                            $isPending  = ! $isApproved && ! $isRejected && ! $isCurrent;
                                        @endphp

                                        @if ($stepNum > 1)
                                            <div class="w-3 h-px {{ $isApproved || $isRejected ? 'bg-gray-400' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                                        @endif

                                        <div title="{{ $stepConfig['label'] }}: {{ $isApproved ? 'Approved' : ($isRejected ? 'Rejected' : ($isCurrent ? 'Awaiting' : 'Pending')) }}"
                                             class="flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-bold
                                                    {{ $isApproved ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : '' }}
                                                    {{ $isRejected ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' : '' }}
                                                    {{ $isCurrent  ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ring-1 ring-amber-400' : '' }}
                                                    {{ $isPending  ? 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' : '' }}">
                                            @if ($isApproved)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @elseif ($isRejected)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                            @else
                                                {{ $stepNum }}
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- Final status if completed --}}
                                    @if ($req->status === 'approved')
                                        <span class="ml-1 text-[10px] font-semibold text-green-600 dark:text-green-400">Done</span>
                                    @elseif ($req->status === 'rejected')
                                        <span class="ml-1 text-[10px] font-semibold text-red-600 dark:text-red-400">Rejected</span>
                                    @elseif ($isCurrent ?? false)
                                        <span class="ml-1 text-[10px] text-amber-600 dark:text-amber-400">Awaiting</span>
                                    @endif
                                </div>
                                {{-- Step labels --}}
                                <div class="flex items-center gap-1 mt-0.5">
                                    @foreach ($steps as $stepNum => $stepConfig)
                                        @if ($stepNum > 1)<div class="w-3"></div>@endif
                                        <div class="w-6 text-center text-[9px] text-gray-400 truncate">{{ strtok($stepConfig['label'], ' ') }}</div>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if ($req->status === 'pending' && $req->approval_step === $myStep)
                                    <div class="flex gap-2">
                                        <button wire:click="openAction({{ $req->id }}, 'approve')"
                                                class="text-green-600 dark:text-green-400 hover:text-green-800 text-xs font-medium">Approve</button>
                                        <button wire:click="openAction({{ $req->id }}, 'reject')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium">Reject</button>
                                    </div>
                                @elseif ($req->status === 'pending')
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Not your step</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No leave requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->pendingRequests->links() }}
        </div>
    </div>

    {{-- Action Modal --}}
    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">
                    {{ $actionType === 'approve' ? 'Approve' : 'Reject' }} Leave Request
                </h4>
                @if ($myStep)
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                    Step {{ $myStep }} of {{ count($steps) }} · {{ $steps[$myStep]['label'] }}
                    @if ($actionType === 'approve' && isset($steps[$myStep + 1]))
                        → will forward to <span class="font-medium">{{ $steps[$myStep + 1]['label'] }}</span>
                    @elseif ($actionType === 'approve')
                        → final approval
                    @endif
                </p>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Remarks {{ $actionType === 'reject' ? '(required)' : '(optional)' }}
                    </label>
                    <textarea wire:model.live="remarks" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400 text-sm"
                              placeholder="Add remarks..."></textarea>
                    @error('remarks') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmAction" @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg transition
                                   {{ $actionType === 'approve' ? 'bg-green-600 dark:bg-green-500 hover:bg-green-700 dark:hover:bg-green-600' : 'bg-red-600 dark:bg-red-500 hover:bg-red-700 dark:hover:bg-red-600' }}">
                        {{ $actionType === 'approve' ? 'Approve' : 'Reject' }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
