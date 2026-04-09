<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Portal') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Prevent dark-mode flash + init Alpine sidebar store -->
        <script>
            if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
            function toggleDarkMode() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', isDark);
            }

            document.addEventListener('alpine:init', () => {
                Alpine.store('sidebar', {
                    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                    toggle() {
                        this.collapsed = !this.collapsed;
                        localStorage.setItem('sidebarCollapsed', this.collapsed);
                    }
                });
            });
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased h-full bg-slate-50 dark:bg-slate-950 text-gray-900 dark:text-gray-100">

        <div class="flex h-full min-h-screen" x-data>

            {{-- Sidebar --}}
            <livewire:layout.sidebar />

            {{-- Main panel — margin tracks sidebar width via store --}}
            <div
                class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out ml-0"
                :class="$store.sidebar.collapsed ? 'sm:ml-16' : 'sm:ml-64'"
            >
                {{-- ── Top header bar ── --}}
                @if (isset($header))
                    <header class="sticky top-0 z-30 bg-white dark:bg-slate-950 border-b border-gray-200 dark:border-slate-800">
                        <div class="flex items-center justify-between h-14 px-6">

                            {{-- Page title --}}
                            <div class="flex items-center gap-3">
                                <div>{{ $header }}</div>
                            </div>

                            {{-- Right controls --}}
                            <div class="flex items-center gap-1.5">
                                {{-- Dark mode toggle --}}
                                <button
                                    onclick="toggleDarkMode()"
                                    class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                                    title="Toggle theme">
                                    <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                    </svg>
                                    <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                                    </svg>
                                </button>

                                {{-- Notification bell --}}
                                @if (class_exists(\App\Livewire\NotificationBell::class))
                                    <livewire:notification-bell />
                                @endif

                                {{-- Date badge --}}
                                <span class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs text-slate-500 dark:text-slate-400 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    {{ now()->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </header>
                @endif

                {{-- ── Page content ── --}}
                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>

                {{-- ── Footer ── --}}
                <footer class="px-6 py-3 border-t border-gray-200 dark:border-slate-800 text-xs text-gray-400 dark:text-slate-600">
                    &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; All rights reserved.
                </footer>

            </div>
        </div>

    </body>
</html>
