<div x-data="{ showModal: false }"
     @open-action-modal.window="showModal = true">

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">Trip Ticket Requests</h3>

        {{-- Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">

            <select wire:model.live="filterStatus"
                    class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                <option value="pending">Pending (my step)</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All</option>
            </select>

            <select wire:model.live="filterDept"
                    class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                <option value="">All Departments</option>
                @foreach (\App\Models\TripTicket::distinct()->pluck('department')->filter()->sort() as $dept)
                    <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
            </select>
        </div>

        {{-- My steps indicator --}}
        @php $mySteps = $this->mySteps; $steps = \App\Services\TripTicketService::APPROVAL_STEPS; @endphp
        @if (count($mySteps))
            <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                You act at:
                @foreach ($mySteps as $s)
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Step {{ $s }} — {{ $steps[$s]['label'] }}</span>
                @endforeach
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Destination</th>
                        <th class="px-4 py-3">Departure</th>
                        <th class="px-4 py-3">Vehicle / Driver</th>
                        <th class="px-4 py-3">Purpose</th>
                        <th class="px-4 py-3">Approval Progress</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->tickets as $ticket)
                        @php
                            $approvedSteps = $ticket->approvals->where('action','approved')->pluck('step')->toArray();
                            $rejectedStep  = $ticket->approvals->where('action','rejected')->first()?->step;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $ticket->employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $ticket->department ?? $ticket->employee->emp_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $ticket->destination_to }}</div>
                                <div class="text-gray-400 dark:text-gray-500">from {{ $ticket->destination_from }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                {{ $ticket->departure_datetime->format('M d, Y') }}
                                <div class="text-gray-400 dark:text-gray-500">{{ $ticket->departure_datetime->format('g:i A') }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                @if ($ticket->vehicle)
                                    <div>{{ $ticket->vehicle->display_name }}</div>
                                @endif
                                @if ($ticket->driver)
                                    <div class="text-gray-400 dark:text-gray-500">{{ $ticket->driver->display_name }}</div>
                                @endif
                                @if (! $ticket->vehicle && ! $ticket->driver)
                                    <span class="text-gray-400 dark:text-gray-500">To be assigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 max-w-[140px] truncate">
                                {{ $ticket->purpose }}
                            </td>

                            {{-- Approval Progress --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    @foreach ($steps as $stepNum => $stepConfig)
                                        @php
                                            $isApproved = in_array($stepNum, $approvedSteps);
                                            $isRejected = $rejectedStep === $stepNum;
                                            $isCurrent  = $ticket->approval_step === $stepNum && $ticket->status === 'pending';
                                            $isPending  = ! $isApproved && ! $isRejected && ! $isCurrent;
                                        @endphp
                                        @if ($stepNum > 1)
                                            <div class="w-3 h-px {{ $isApproved ? 'bg-gray-400' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
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
                                    @if ($ticket->status === 'approved')
                                        <span class="ml-1 text-[10px] font-semibold text-green-600 dark:text-green-400">Scheduled</span>
                                    @elseif ($ticket->status === 'rejected')
                                        <span class="ml-1 text-[10px] font-semibold text-red-600 dark:text-red-400">Denied</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    @foreach ($steps as $stepNum => $stepConfig)
                                        @if ($stepNum > 1)<div class="w-3"></div>@endif
                                        <div class="w-6 text-center text-[9px] text-gray-400 truncate">{{ strtok($stepConfig['label'], ' ') }}</div>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if ($ticket->status === 'pending' && in_array($ticket->approval_step, $mySteps))
                                    <div class="flex gap-2">
                                        <button wire:click="openAction({{ $ticket->id }}, 'approve')"
                                                class="text-green-600 dark:text-green-400 hover:text-green-800 text-xs font-medium">
                                            {{ $ticket->approval_step === 3 ? 'Schedule' : 'Approve' }}
                                        </button>
                                        <button wire:click="openAction({{ $ticket->id }}, 'reject')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium">
                                            {{ $ticket->approval_step === 3 ? 'Deny' : 'Reject' }}
                                        </button>
                                    </div>
                                @elseif ($ticket->status === 'approved')
                                    <button wire:click="markReturned({{ $ticket->id }})"
                                            wire:confirm="Mark this vehicle as returned and trip completed?"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Mark Returned
                                    </button>
                                @elseif ($ticket->status === 'completed')
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="text-green-600 dark:text-green-400 font-medium">Returned</span>
                                        @if ($ticket->completed_at)
                                            <div class="text-gray-400 dark:text-gray-500">{{ $ticket->completed_at->format('M d, Y') }}</div>
                                        @endif
                                    </div>
                                @elseif ($ticket->status === 'pending')
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Not your step</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No trip ticket requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->tickets->links() }}</div>
    </div>

    {{-- Action Modal --}}
    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>

                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">
                    {{ $actionType === 'approve' ? ($selectedId && \App\Models\TripTicket::find($selectedId)?->approval_step === 3 ? 'Schedule' : 'Approve') : ($selectedId && \App\Models\TripTicket::find($selectedId)?->approval_step === 3 ? 'Deny' : 'Reject') }} Trip Ticket
                </h4>

                @php
                    $selTicket = $selectedId ? \App\Models\TripTicket::with(['vehicle','driver.employee'])->find($selectedId) : null;
                    $selStep   = $selTicket?->approval_step;
                @endphp

                @if ($selStep)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                        Step {{ $selStep }} of {{ count($steps) }} · {{ $steps[$selStep]['label'] ?? '' }}
                        @if ($actionType === 'approve' && isset($steps[$selStep + 1]))
                            → forward to <span class="font-medium">{{ $steps[$selStep + 1]['label'] }}</span>
                        @elseif ($actionType === 'approve')
                            → final approval (vehicle scheduled)
                        @endif
                    </p>
                @endif

                {{-- Fleet step: vehicle/driver assignment --}}
                @if ($selStep === 3 && $actionType === 'approve')
                    <div class="grid grid-cols-1 gap-3 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Assign Vehicle & Driver (optional)</p>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Vehicle</label>
                            <select wire:model.live="assignVehicleId"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Keep current / None —</option>
                                @foreach (\App\Models\Vehicle::where('is_active', true)->orderBy('make')->get() as $v)
                                    <option value="{{ $v->id }}">{{ $v->display_name }} ({{ $v->status }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Driver</label>
                            <select wire:model.live="assignDriverId"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Keep current / None —</option>
                                @foreach (\App\Models\Driver::where('is_active', true)->with('employee')->get() as $d)
                                    <option value="{{ $d->id }}">{{ $d->employee->full_name ?? '—' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Remarks {{ $actionType === 'reject' ? '(required)' : '(optional)' }}
                    </label>
                    <textarea wire:model.live="remarks" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
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
                                   {{ $actionType === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                        {{ $actionType === 'approve' ? ($selStep === 3 ? 'Confirm Schedule' : 'Approve') : ($selStep === 3 ? 'Deny' : 'Reject') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
