<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Present --}}
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
            <div class="p-3 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Days Present</p>
                <p class="text-2xl font-bold text-gray-800">{{ $this->totalPresent }}</p>
            </div>
        </div>

        {{-- Total Absent --}}
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
            <div class="p-3 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Days Absent</p>
                <p class="text-2xl font-bold text-gray-800">{{ $this->totalAbsent }}</p>
            </div>
        </div>

        {{-- Total Late --}}
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
            <div class="p-3 bg-yellow-100 rounded-lg">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Days Late</p>
                <p class="text-2xl font-bold text-gray-800">{{ $this->totalLate }}</p>
            </div>
        </div>

        {{-- Total Hours --}}
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
            <div class="p-3 bg-indigo-100 rounded-lg">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Hours</p>
                <p class="text-2xl font-bold text-gray-800">{{ $this->totalHours }}</p>
            </div>
        </div>
    </div>
</div>
