<div x-data="{ showCancelModal: false }"
     @open-cancel-modal.window="showCancelModal = true">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">My Trip Tickets</h3>
            <a href="{{ route('trip-ticket.request') }}" wire:navigate
               class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                + New Request
            </a>
        </div>

        {{-- Filter --}}
        <div class="mb-4">
            <select wire:model.live="filterStatus"
                    class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Scheduled</option>
                <option value="completed">Returned</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Destination</th>
                        <th class="px-4 py-3">Departure</th>
                        <th class="px-4 py-3">Vehicle / Driver</th>
                        <th class="px-4 py-3">Purpose</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Progress</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->tickets as $ticket)
                        @php
                            $approvedSteps = $ticket->approvals->where('action','approved')->pluck('step')->toArray();
                            $rejectedStep  = $ticket->approvals->where('action','rejected')->first()?->step;
                            $steps         = \App\Services\TripTicketService::APPROVAL_STEPS;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="text-gray-800 dark:text-gray-100 font-medium">{{ $ticket->destination_to }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">from {{ $ticket->destination_from }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                {{ $ticket->departure_datetime->format('M d, Y g:i A') }}
                                @if ($ticket->return_datetime)
                                    <div class="text-gray-400 dark:text-gray-500">Return: {{ $ticket->return_datetime->format('M d, Y g:i A') }}</div>
                                @endif
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
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 max-w-[160px] truncate">
                                {{ $ticket->purpose }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $onTrip = $ticket->status === 'approved' && $ticket->departed_at !== null;
                                    $badge = match(true) {
                                        $onTrip                        => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        $ticket->status === 'approved'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        $ticket->status === 'completed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        $ticket->status === 'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        $ticket->status === 'cancelled' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                        default                         => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    };
                                    $label = match(true) {
                                        $onTrip                        => 'On Trip',
                                        $ticket->status === 'approved'  => 'Scheduled',
                                        $ticket->status === 'completed' => 'Returned',
                                        default                         => ucfirst($ticket->status),
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $badge }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    @foreach ($steps as $stepNum => $stepConfig)
                                        @php
                                            $isApproved = in_array($stepNum, $approvedSteps);
                                            $isRejected = $rejectedStep === $stepNum;
                                            $isCurrent  = $ticket->approval_step === $stepNum && $ticket->status === 'pending';
                                        @endphp
                                        @if ($stepNum > 1)
                                            <div class="w-3 h-px {{ $isApproved ? 'bg-gray-400' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                                        @endif
                                        <div title="{{ $stepConfig['label'] }}"
                                             class="flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-bold
                                                    {{ $isApproved ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : '' }}
                                                    {{ $isRejected ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' : '' }}
                                                    {{ $isCurrent  ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ring-1 ring-amber-400' : '' }}
                                                    {{ !$isApproved && !$isRejected && !$isCurrent ? 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' : '' }}">
                                            @if ($isApproved)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @elseif ($isRejected)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                            @else
                                                {{ $stepNum }}
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($ticket->status === 'pending')
                                    <button wire:click="confirmCancel({{ $ticket->id }})"
                                            @click="showCancelModal = true"
                                            class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 font-medium">
                                        Cancel
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                No trip tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $this->tickets->links() }}</div>
    </div>

    {{-- Cancel Confirm Modal --}}
    <template x-teleport="body">
        <div x-show="showCancelModal" x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showCancelModal = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-sm mx-4" @click.stop>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Cancel Trip Ticket?</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">This action cannot be undone.</p>
                <div class="flex justify-end gap-3">
                    <button @click="showCancelModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Keep
                    </button>
                    <button wire:click="cancelTicket" @click="showCancelModal = false"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        Cancel Ticket
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
