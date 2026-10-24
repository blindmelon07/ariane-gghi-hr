<div x-data="{ showModal: false }"
     @open-guard-modal.window="showModal = true">

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <x-phosphor-shield-check-bold class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Vehicle Gate Log</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Confirm vehicle departures and returns at the gate.</p>
                </div>
            </div>
            <input aria-label="Search employee or plate" type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search employee or plate..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 w-full sm:w-64">
        </div>
    </div>

    {{-- ── Section 1: Ready to Depart ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
            Ready to Depart
            <span class="ml-1 px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-semibold">
                {{ $this->readyToDepart->count() }}
            </span>
        </h4>

        @forelse ($this->readyToDepart as $ticket)
            @include('livewire.security._trip-card', ['ticket' => $ticket, 'action' => 'depart'])
        @empty
            <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No vehicles scheduled to depart.</p>
        @endforelse
    </div>

    {{-- ── Section 2: Currently Out ────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
            Currently Out
            <span class="ml-1 px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-semibold">
                {{ $this->currentlyOut->count() }}
            </span>
        </h4>

        @forelse ($this->currentlyOut as $ticket)
            @include('livewire.security._trip-card', ['ticket' => $ticket, 'action' => 'return'])
        @empty
            <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No vehicles currently out.</p>
        @endforelse
    </div>

    {{-- ── Section 3: Recently Returned ───────────────────────────────────────── --}}
    @if ($this->recentReturns->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
            <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
            Recently Returned
        </h4>
        <div class="space-y-2">
            @foreach ($this->recentReturns as $r)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                    <div class="text-sm min-w-0">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $r->vehicle?->display_name ?? 'No vehicle' }}</span>
                        <span class="text-gray-400 mx-1">·</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $r->employee->full_name }}</span>
                        @if ($r->return_remarks)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 italic">"{{ $r->return_remarks }}"</p>
                        @endif
                    </div>
                    <div class="text-right text-xs text-gray-400 dark:text-gray-500 shrink-0 ml-4">
                        <div>{{ $r->completed_at?->format('M d, Y') }}</div>
                        <div>{{ $r->completed_at?->format('g:i A') }}</div>
                        @if ($r->completedBy)
                            <div class="text-[10px]">by {{ $r->completedBy->name }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Shared Action Modal ─────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>

                @php
                    $t      = $activeId ? \App\Models\TripTicket::with(['vehicle','employee','driver.employee'])->find($activeId) : null;
                    $isDep  = $activeAction === 'depart';
                @endphp

                {{-- Modal header --}}
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                {{ $isDep ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-green-100 dark:bg-green-900/30' }}">
                        @if ($isDep)
                            <x-phosphor-arrow-right-bold class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        @else
                            <x-phosphor-check-bold class="w-5 h-5 text-green-600 dark:text-green-400" />
                        @endif
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ $isDep ? 'Confirm Departure' : 'Confirm Return' }}
                        </h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $isDep ? 'Vehicle is leaving the premises.' : 'Vehicle has arrived back at the gate.' }}
                        </p>
                    </div>
                </div>

                {{-- Trip summary --}}
                @if ($t)
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3 mb-4 text-sm space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Vehicle</span>
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $t->vehicle?->display_name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Driver</span>
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $t->driver?->display_name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Requestor</span>
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $t->employee->full_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Destination</span>
                            <span class="font-medium text-gray-800 dark:text-gray-100 text-right max-w-[200px] truncate">{{ $t->destination_to }}</span>
                        </div>
                        @if (! $isDep && $t->departed_at)
                            <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-1.5 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Departed at</span>
                                <span class="font-medium text-gray-800 dark:text-gray-100">{{ $t->departed_at->format('M d, g:i A') }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Remarks — only for return --}}
                @if (! $isDep)
                    <div class="mb-5">
                        <label for="return-remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Return Remarks
                            <span class="text-gray-400 font-normal">(vehicle condition, mileage, notes)</span>
                        </label>
                        <textarea id="return-remarks" wire:model.live="return_remarks" rows="3"
                                  placeholder="e.g. Vehicle in good condition. Odometer: 45,230 km."
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 text-sm"></textarea>
                        @error('return_remarks') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <button @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmAction" @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg transition flex items-center gap-2
                                   {{ $isDep ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700' }}">
                        @if ($isDep)
                            <x-phosphor-arrow-right-bold class="w-4 h-4" />
                            Confirm Departure
                        @else
                            <x-phosphor-check-bold class="w-4 h-4" />
                            Confirm Return
                        @endif
                    </button>
                </div>

            </div>
        </div>
    </template>
</div>
