<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Commodity Allowance Form</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($this->existing)
                        Submitted {{ $this->existing->submitted_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}. You can update it if anything changes.
                    @else
                        As a regular employee, please fill in this form for HR.
                    @endif
                </p>
            </div>
            @if ($this->existing)
                <a href="{{ route('commodity-form.show', $this->existing) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">View / Print</a>
            @endif
        </div>

        @php
            $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm';
            $label = 'block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1';
            $err   = 'text-red-500 dark:text-red-400 text-xs mt-1';
        @endphp

        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $label }}">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="lastName" class="{{ $input }}" />
                    @error('lastName') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">First Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="firstName" class="{{ $input }}" />
                    @error('firstName') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Middle Name</label>
                    <input type="text" wire:model="middleName" class="{{ $input }}" />
                    @error('middleName') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Position <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="position" class="{{ $input }}" />
                    @error('position') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Religion</label>
                    <input type="text" wire:model="religion" class="{{ $input }}" />
                    @error('religion') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Address <span class="text-red-500">*</span></label>
                <input type="text" wire:model="address" class="{{ $input }}" />
                @error('address') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Birthdate <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="birthdate" max="{{ now()->toDateString() }}" class="{{ $input }}" />
                    @error('birthdate') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <span class="{{ $label }}">Civil Status <span class="text-red-500">*</span></span>
                    <div class="flex flex-wrap gap-4 pt-2">
                        @foreach (\App\Models\CommodityAllowanceForm::CIVIL_STATUSES as $value => $text)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="radio" wire:model.live="civilStatus" value="{{ $value }}" class="text-indigo-600" /> {{ $text }}
                            </label>
                        @endforeach
                    </div>
                    @error('civilStatus') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($civilStatus === 'married')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Name of Spouse <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="spouseName" class="{{ $input }}" />
                        @error('spouseName') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Birthdate of Spouse <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="spouseBirthdate" max="{{ now()->toDateString() }}" class="{{ $input }}" />
                        @error('spouseBirthdate') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="{{ $label }}">GSAC Membership <span class="text-red-500">*</span></span>
                    <div class="flex flex-wrap gap-4 pt-2">
                        @foreach (\App\Models\CommodityAllowanceForm::MEMBERSHIPS as $value => $text)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="radio" wire:model.live="gsacMembership" value="{{ $value }}" class="text-indigo-600" /> {{ $text }}
                            </label>
                        @endforeach
                    </div>
                    @error('gsacMembership') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                @if (in_array($gsacMembership, ['regular', 'associate'], true))
                    <div>
                        <label class="{{ $label }}">Passbook Number <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="passbookNumber" class="{{ $input }}" />
                        @error('passbookNumber') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                <div>
                    <label class="{{ $label }}">Name and Signature <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="signatureName" placeholder="Type your full name" class="{{ $input }}" />
                    @error('signatureName') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model="agree" class="mt-0.5 rounded text-indigo-600" />
                    I confirm that the information above is true and correct, and I sign this form with my typed name.
                </label>
                @error('agree') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <span wire:loading.remove wire:target="save">{{ $this->existing ? 'Update Form' : 'Submit Form' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
