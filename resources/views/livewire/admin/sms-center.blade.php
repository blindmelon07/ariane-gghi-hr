<div class="space-y-4">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-2 px-4 py-3 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    @unless ($this->configured)
        <div class="px-4 py-3 text-sm text-amber-800 border border-amber-200 rounded-lg bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300">
            SMS is not set up yet. Add <span class="font-mono">UNISMS_SECRET_KEY</span> and <span class="font-mono">UNISMS_SENDER_ID</span> to the <span class="font-mono">.env</span> file, then run <span class="font-mono">php artisan config:clear</span>.
        </div>
    @endunless

    {{-- Header: tabs + credits --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex flex-wrap p-1 bg-gray-100 rounded-lg dark:bg-gray-800">
            @foreach (['compose' => 'Send Message', 'credentials' => 'Login Details', 'alerts' => 'Attendance Alerts', 'push' => 'App Notifications', 'history' => 'History'] as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $tab === $key ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            <span>SMS credits:</span>
            <span class="font-semibold text-gray-900 dark:text-white">{{ $credits !== null ? number_format($credits) : '—' }}</span>
            <button wire:click="refreshCredits" class="p-1 text-gray-400 rounded hover:text-blue-600" title="Refresh">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>
    </div>

    {{-- ── Send Message ─────────────────────────────────────────── --}}
    @if ($tab === 'compose')
        <div class="p-5 space-y-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Send to</label>
                <div class="flex flex-wrap gap-2">
                    @foreach (['all' => 'All employees', 'department' => 'A department', 'employees' => 'Selected employees', 'numbers' => 'Other numbers'] as $key => $label)
                        <label class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border rounded-lg cursor-pointer {{ $audience === $key ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300' }}">
                            <input type="radio" wire:model.live="audience" value="{{ $key }}" class="text-blue-600">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('audience') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            @if ($audience === 'department')
                <div>
                    <select wire:model.live="departmentId" class="w-full text-sm border-gray-300 rounded-lg sm:w-72 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Choose a department…</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('departmentId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($audience === 'employees')
                <div>
                    <input wire:model.live.debounce.300ms="employeeSearch" type="text" placeholder="Search employees…"
                           class="w-full mb-2 text-sm border-gray-300 rounded-lg sm:w-72 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <div class="overflow-y-auto border border-gray-200 divide-y divide-gray-100 rounded-lg max-h-64 dark:border-gray-700 dark:divide-gray-700">
                        @forelse ($this->employees as $emp)
                            <label wire:key="se-{{ $emp->id }}" class="flex items-center gap-3 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <input type="checkbox" wire:model.live="selectedEmployeeIds" value="{{ $emp->id }}" class="text-blue-600 rounded">
                                <span class="flex-1 text-gray-900 dark:text-white">{{ $emp->full_name }}</span>
                                <span class="text-xs text-gray-400">{{ $emp->dept?->name }}</span>
                                <span class="font-mono text-xs {{ $emp->cell_number ? 'text-gray-500' : 'text-red-400' }}">{{ $emp->cell_number ?: 'no number' }}</span>
                            </label>
                        @empty
                            <p class="px-3 py-4 text-sm text-center text-gray-400">No employees found.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($audience === 'numbers')
                <div>
                    <textarea wire:model.live.debounce.300ms="customNumbers" rows="2" placeholder="09171234567, 09281234567"
                              class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    <p class="mt-1 text-xs text-gray-400">Separate numbers with commas, spaces or new lines.</p>
                    @error('customNumbers') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($audience !== 'numbers')
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Send via</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border rounded-lg cursor-pointer {{ $viaApp ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300' }}">
                            <input type="checkbox" wire:model.live="viaApp" class="text-blue-600 rounded">
                            App notification <span class="text-xs opacity-75">(bell + phone pop-up, free)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border rounded-lg cursor-pointer {{ $viaSms ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300' }}">
                            <input type="checkbox" wire:model.live="viaSms" class="text-blue-600 rounded">
                            SMS <span class="text-xs opacity-75">(uses credits)</span>
                        </label>
                    </div>
                    @error('viaApp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                @if ($viaApp)
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Notification title <span class="font-normal text-gray-400">(optional — shown in bold on the pop-up)</span>
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="notifTitle" maxlength="80"
                               placeholder="{{ $audience === 'employees' ? 'Message from HR' : 'Announcement' }}"
                               class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('notifTitle') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif
            @endif

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                <textarea wire:model.live.debounce.300ms="smsMessage" rows="4" maxlength="{{ \App\Services\SmsService::MAX_LENGTH }}"
                          placeholder="GGHI HR: Good day {first_name}! …"
                          class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                <div class="flex justify-between mt-1 text-xs text-gray-400">
                    <span>@if ($audience !== 'numbers') <span class="font-mono">{first_name}</span> and <span class="font-mono">{name}</span> are replaced with each employee's name. @endif</span>
                    @if ($viaSms || $audience === 'numbers')
                        <span class="{{ mb_strlen($smsMessage) > 0 && mb_strlen($smsMessage) < \App\Services\SmsService::MIN_LENGTH ? 'text-red-500' : '' }}">min {{ \App\Services\SmsService::MIN_LENGTH }} · {{ mb_strlen($smsMessage) }} / {{ \App\Services\SmsService::MAX_LENGTH }} · {{ max(1, (int) ceil(mb_strlen($smsMessage) / 160)) }} SMS part(s)</span>
                    @else
                        <span>{{ mb_strlen($smsMessage) }} / {{ \App\Services\SmsService::MAX_LENGTH }}</span>
                    @endif
                </div>
                @error('smsMessage') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            @php
                $count = $audience === 'numbers' ? count($this->numberList) : $this->recipients->count();
                $withNumber = $audience === 'numbers' ? $count : $this->recipients->filter(fn ($e) => \App\Services\SmsService::normalize($e->cell_number))->count();
                $sendApp    = $viaApp && $audience !== 'numbers';
                $sendSms    = $viaSms || $audience === 'numbers';
                $withLogin  = $sendApp ? $this->recipients->filter(fn ($e) => $e->user_id)->count() : 0;
                $buttonText = match (true) {
                    $sendApp && $sendSms => 'Send Notification + SMS',
                    $sendApp             => 'Send Notification',
                    default              => 'Send SMS',
                };
                $confirm = collect([
                    $sendApp ? "{$withLogin} app notification(s)" : null,
                    $sendSms ? "{$withNumber} SMS" : null,
                ])->filter()->implode(' and ');
            @endphp
            <div class="flex flex-col gap-3 pt-3 border-t border-gray-100 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>{{ $count }} recipient(s)</p>
                    @if ($sendApp && $withLogin < $count)
                        <p class="text-amber-600">{{ $count - $withLogin }} without a login account won't get the app notification</p>
                    @endif
                    @if ($sendSms && $audience !== 'numbers' && $withNumber < $count)
                        <p class="text-amber-600">{{ $count - $withNumber }} without a valid number won't get the SMS</p>
                    @endif
                </div>
                <button wire:click="send" wire:loading.attr="disabled"
                        wire:confirm="Send {{ $confirm }}?"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="send">{{ $buttonText }}</span>
                    <span wire:loading wire:target="send">Sending…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ── Login Details ────────────────────────────────────────── --}}
    @if ($tab === 'credentials')
        <div class="p-5 space-y-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Each selected employee gets a <strong>new random password</strong> by text, along with their employee code and the login link.
                Employees without an account get one (role: employee). Their password only changes if the text is accepted.
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <input wire:model.live.debounce.300ms="credentialSearch" type="text" placeholder="Search employees…"
                       class="w-full text-sm border-gray-300 rounded-lg sm:w-72 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <button wire:click="toggleAllCredentials" class="text-sm font-medium text-blue-600 dark:text-blue-400">Select / clear all shown</button>
            </div>

            <div class="overflow-y-auto border border-gray-200 divide-y divide-gray-100 rounded-lg max-h-96 dark:border-gray-700 dark:divide-gray-700">
                @forelse ($this->credentialEmployees as $emp)
                    @php $valid = \App\Services\SmsService::normalize($emp->cell_number); @endphp
                    <label wire:key="ce-{{ $emp->id }}" class="flex items-center gap-3 px-3 py-2 text-sm {{ $valid ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30' : 'opacity-60' }}">
                        <input type="checkbox" wire:model.live="credentialIds" value="{{ $emp->id }}" @disabled(! $valid) class="text-blue-600 rounded">
                        <span class="font-mono text-xs text-gray-500 w-14 shrink-0">{{ $emp->emp_code }}</span>
                        <span class="flex-1 min-w-0 truncate text-gray-900 dark:text-white">{{ $emp->full_name }}</span>
                        @if ($emp->user)
                            <span class="shrink-0 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Has account</span>
                        @else
                            <span class="shrink-0 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No account</span>
                        @endif
                        <span class="shrink-0 font-mono text-xs w-24 text-right {{ $valid ? 'text-gray-500' : 'text-red-400' }}">{{ $emp->cell_number ?: 'no number' }}</span>
                    </label>
                @empty
                    <p class="px-3 py-4 text-sm text-center text-gray-400">No employees found.</p>
                @endforelse
            </div>
            @error('credentialIds') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($credentialIds) }} selected</p>
                <button wire:click="sendCredentials" wire:loading.attr="disabled"
                        wire:confirm="Reset the password of {{ count($credentialIds) }} employee(s) and text them their new login?"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="sendCredentials">Send Login Details</span>
                    <span wire:loading wire:target="sendCredentials">Sending…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ── Attendance Alerts ────────────────────────────────────── --}}
    @if ($tab === 'alerts')
        <div class="p-5 space-y-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <p>Texts employees who were <strong>late</strong> or had <strong>no attendance</strong> on the chosen day. Sundays, day-offs, holidays and approved leave are skipped, and Nursing staff (rotating shifts) are excluded. Nobody is texted twice for the same day.</p>
                <p class="text-gray-500 dark:text-gray-400">
                    Automatic daily alerts at 8:00 PM are
                    <strong class="{{ config('services.unisms.attendance_alerts') ? 'text-green-600' : 'text-gray-600 dark:text-gray-300' }}">{{ config('services.unisms.attendance_alerts') ? 'ON' : 'OFF' }}</strong>
                    (<span class="font-mono">UNISMS_ATTENDANCE_ALERTS</span> in .env).
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Day</label>
                    <input type="date" wire:model="alertDate" max="{{ now('Asia/Manila')->toDateString() }}"
                           class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('alertDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <button wire:click="runAttendanceAlerts" wire:loading.attr="disabled"
                        wire:confirm="Text late and absent employees for this day?"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="runAttendanceAlerts">Send Alerts Now</span>
                    <span wire:loading wire:target="runAttendanceAlerts">Checking attendance…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ── App Notifications ────────────────────────────────────── --}}
    @if ($tab === 'push')
        <div class="p-5 space-y-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
            @php $devices = $this->pushDevices; @endphp
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <p><strong class="text-gray-900 dark:text-white">{{ $devices->count() }}</strong> device(s) from
                        <strong class="text-gray-900 dark:text-white">{{ $devices->pluck('user_id')->unique()->count() }}</strong> user(s) have phone notifications turned on.</p>
                    <p class="text-gray-500 dark:text-gray-400">Employees turn them on from the bell → <em>Turn on phone notifications</em>. The test is app-only: no SMS, no credits.</p>
                </div>
                <button wire:click="sendTestToAllDevices" wire:loading.attr="disabled"
                        wire:confirm="Send a test notification to all {{ $devices->count() }} device(s)?"
                        @disabled($devices->isEmpty())
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg shrink-0 hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="sendTestToAllDevices">Send Test to All Devices</span>
                    <span wire:loading wire:target="sendTestToAllDevices">Sending…</span>
                </button>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">User</th>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Device</th>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Turned on</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($devices as $device)
                            @php
                                $ua = (string) $device->user_agent;
                                $os = match (true) {
                                    str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iPhone / iPad',
                                    str_contains($ua, 'Android')                            => 'Android',
                                    str_contains($ua, 'Windows')                            => 'Windows',
                                    str_contains($ua, 'Mac OS')                             => 'Mac',
                                    default                                                 => 'Other',
                                };
                                $browser = match (true) {
                                    str_contains($ua, 'Edg/')                                   => 'Edge',
                                    str_contains($ua, 'Firefox')                                => 'Firefox',
                                    str_contains($ua, 'Chrome')                                 => 'Chrome',
                                    str_contains($ua, 'Safari')                                 => 'Safari',
                                    default                                                     => '',
                                };
                            @endphp
                            <tr wire:key="dev-{{ $device->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                    {{ $device->user?->name ?? '—' }}
                                    <span class="font-mono text-xs text-gray-400">{{ $device->user?->employee_code }}</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $os }}{{ $browser ? " · {$browser}" : '' }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $device->updated_at->format('M d, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-sm text-center text-gray-400">No devices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── History ──────────────────────────────────────────────── --}}
    @if ($tab === 'history')
        <div class="flex flex-wrap gap-2">
            <select wire:model.live="filterType" class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All types</option>
                @foreach (['announcement', 'custom', 'payslip', 'leave', 'overtime', 'request', 'attendance', 'credentials'] as $t)
                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus" class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All statuses</option>
                @foreach (['queued', 'failed', 'skipped', 'pending'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Sent</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">To</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Type</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Message</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->logs as $log)
                        <tr wire:key="log-{{ $log->id }}" class="align-top">
                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $log->created_at->format('M d, h:i A') }}</td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                <p class="text-gray-900 dark:text-white">{{ $log->employee?->full_name ?? '—' }}</p>
                                <p class="font-mono text-xs text-gray-400">{{ $log->recipient }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">{{ ucfirst($log->type) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-md">{{ $log->content }}</td>
                            <td class="px-4 py-3 text-xs">
                                @php
                                    $badge = match ($log->status) {
                                        'queued'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'failed'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'skipped' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        default   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full font-medium whitespace-nowrap {{ $badge }}">{{ $log->status === 'queued' ? 'Sent' : ucfirst($log->status) }}</span>
                                @if ($log->error)
                                    <p class="mt-1 text-red-500 dark:text-red-400 max-w-xs break-words">{{ $log->error }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-sm text-center text-gray-400">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($this->logs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $this->logs->links() }}</div>
            @endif
        </div>
    @endif
</div>
