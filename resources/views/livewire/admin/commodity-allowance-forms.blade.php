<div>
    <div class="grid grid-cols-3 gap-3 mb-6">
        @foreach (['regular' => ['Regular employees', 'text-gray-800 dark:text-gray-100'], 'submitted' => ['Submitted', 'text-green-600 dark:text-green-400'], 'missing' => ['Not yet submitted', 'text-amber-600 dark:text-amber-400']] as $key => [$text, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $text }}</p>
                <p class="text-2xl font-bold {{ $color }}">{{ $this->counts[$key] }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
               class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
        <select wire:model.live="filter" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="all">All regular employees</option>
            <option value="submitted">Submitted</option>
            <option value="missing">Not yet submitted</option>
        </select>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        @foreach (['Employee', 'Civil Status', 'GSAC Membership', 'Passbook No.', 'Submitted', ''] as $h)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->employees as $emp)
                        @php $form = $emp->commodityAllowanceForm; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $emp->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $emp->emp_code }}</div>
                            </td>
                            @if ($form)
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ \App\Models\CommodityAllowanceForm::CIVIL_STATUSES[$form->civil_status] ?? $form->civil_status }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ \App\Models\CommodityAllowanceForm::MEMBERSHIPS[$form->gsac_membership] ?? $form->gsac_membership }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $form->passbook_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $form->submitted_at->timezone('Asia/Manila')->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('commodity-form.show', $form) }}" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">View</a>
                                </td>
                            @else
                                <td colspan="5" class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Not yet submitted</span>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $this->employees->links() }}</div>
</div>
