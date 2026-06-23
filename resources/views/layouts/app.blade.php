<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Portal') }}</title>

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#4f46e5">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'GGHI HR') }}">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Prevent dark-mode flash + sidebar layout flash + init Alpine sidebar store -->
        <script>
            // Dark mode
            if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
            function toggleDarkMode() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', isDark);
            }

            // Sidebar pre-init: apply correct width & margin synchronously before Alpine
            // loads, so there is no layout flash on first paint.
            (function () {
                var collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                var w = collapsed ? '4rem' : '16rem';
                var s = document.createElement('style');
                s.id = '__sb_init';
                s.textContent =
                    '.sb-pre{width:' + w + '!important;transition:none!important}' +
                    '@media(min-width:640px){.main-pre{margin-left:' + w + '!important;transition:none!important}}' +
                    '@media(max-width:639px){.sb-pre{transform:translateX(-100%)!important}}';
                document.head.appendChild(s);
            })();

            document.addEventListener('alpine:init', function () {
                Alpine.store('sidebar', {
                    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                    mobileOpen: false,
                    isDesktop: window.innerWidth >= 640,
                    toggle: function () {
                        this.collapsed = !this.collapsed;
                        localStorage.setItem('sidebarCollapsed', this.collapsed);
                    },
                    openMobile: function () { this.mobileOpen = true; },
                    closeMobile: function () { this.mobileOpen = false; }
                });

                window.addEventListener('resize', function () {
                    var store = Alpine.store('sidebar');
                    store.isDesktop = window.innerWidth >= 640;
                    if (store.isDesktop) store.mobileOpen = false;
                });
            });

            // Once Alpine has painted its initial state, remove the override style
            // so the normal Tailwind transitions take over for subsequent toggles.
            document.addEventListener('alpine:initialized', function () {
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        var s = document.getElementById('__sb_init');
                        if (s) s.remove();
                    });
                });
            });
        </script>

        <style>[x-cloak]{display:none!important}</style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('layouts._alpine')
        @livewireStyles
    </head>
    <body class="font-sans antialiased h-full bg-slate-50 dark:bg-slate-950 text-gray-900 dark:text-gray-100">

        <div class="flex h-full min-h-screen" x-data>

            {{-- Mobile backdrop --}}
            <div
                x-cloak
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm sm:hidden"
                x-show="$store.sidebar.mobileOpen"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="$store.sidebar.closeMobile()"
            ></div>

            {{-- Sidebar --}}
            <livewire:layout.sidebar />

            {{-- Main panel — margin tracks sidebar width via store --}}
            <div
                class="main-pre flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
                :class="$store.sidebar.collapsed ? 'sm:ml-16' : 'sm:ml-64'"
            >
                {{-- ── Top header bar ── --}}
                @if (isset($header))
                    <header class="sticky top-0 z-30 bg-white dark:bg-slate-950 border-b border-gray-200 dark:border-slate-800">
                        <div class="flex items-center justify-between h-14 px-4 sm:px-6">

                            {{-- Hamburger (mobile) + Page title --}}
                            <div class="flex items-center gap-3">
                                {{-- Hamburger — mobile only --}}
                                <button
                                    class="sm:hidden p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                                    @click="$store.sidebar.openMobile()"
                                    title="Open menu">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                    </svg>
                                </button>
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

        @livewireScripts
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
    </body>
</html>
