<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">File Leave Request</h3>

        <form wire:submit="submit" class="space-y-5">
            {{-- Leave Type --}}
            <div>
                <label for="leave_type_id" class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                <select wire:model.live="leave_type_id" id="leave_type_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Select leave type</option>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                    @endforeach
                </select>
                @error('leave_type_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @if ($this->leave_type_id)
                    <p class="text-xs text-gray-500 mt-1">Remaining: <span class="font-semibold">{{ $this->remainingCredits }}</span> day(s)</p>
                @endif
            </div>

            {{-- Date Range --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" wire:model.live="start_date" id="start_date"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" wire:model.live="end_date" id="end_date"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($this->totalDays > 0)
                <div class="rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                    Total working days: <span class="font-bold">{{ $this->totalDays }}</span>
                </div>
            @endif

            {{-- Reason --}}
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea wire:model="reason" id="reason" rows="3"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                          placeholder="Briefly describe the reason for your leave..."></textarea>
                @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @error('general') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
