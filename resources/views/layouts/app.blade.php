<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <style>
            /* Safe area support for notch / home indicator */
            :root { --sat: env(safe-area-inset-top,0px); --sab: env(safe-area-inset-bottom,0px); }
            /* Touch improvements */
            button, a, [role="button"] { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
            /* Smooth scroll on iOS */
            .overflow-y-auto, .overflow-x-auto { -webkit-overflow-scrolling: touch; }
            /* Mobile bottom-nav spacing */
            @media (max-width: 1023px) { .mobile-content-pb { padding-bottom: calc(4rem + var(--sab)); } }
            /* All tables scroll horizontally on mobile */
            @media (max-width: 1023px) {
                .overflow-x-auto, [class*="overflow-x-auto"] { -webkit-overflow-scrolling: touch; }
                .bg-white table, .bg-gray-800 table,
                .rounded-xl table { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            }
            /* Modals full-screen on mobile */
            @media (max-width: 639px) {
                .fixed.inset-0.z-50 > div:not([class*="max-w-sm"]) {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-height: 100dvh;
                    border-radius: 0 !important;
                    margin: 0 !important;
                }
            }
            /* Admin page containers full-width on mobile */
            @media (max-width: 1023px) {
                .max-w-7xl { padding-left: 0; padding-right: 0; }
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Portal') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="/images/gghilogoapp.png">

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json">

        <!-- Android / Chrome -->
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="theme-color" content="#0d1b2e">

        <!-- iOS Safari PWA -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="GGHI HR">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <!-- iOS Home Screen Icon (180×180 with background) -->
        <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
        <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">

        <!-- iOS Splash Screens — one per device -->
        {{-- iPhone SE / 8 / 7 / 6s --}}
        <link rel="apple-touch-startup-image" media="(device-width:375px) and (device-height:667px) and (-webkit-device-pixel-ratio:2) and (orientation:portrait)" href="/images/splash/iphone-se.png">
        {{-- iPhone X / XS / 11 Pro --}}
        <link rel="apple-touch-startup-image" media="(device-width:375px) and (device-height:812px) and (-webkit-device-pixel-ratio:3) and (orientation:portrait)" href="/images/splash/iphone-x.png">
        {{-- iPhone XR / 11 --}}
        <link rel="apple-touch-startup-image" media="(device-width:414px) and (device-height:896px) and (-webkit-device-pixel-ratio:2) and (orientation:portrait)" href="/images/splash/iphone-xr.png">
        {{-- iPhone 12 / 13 / 14 --}}
        <link rel="apple-touch-startup-image" media="(device-width:390px) and (device-height:844px) and (-webkit-device-pixel-ratio:3) and (orientation:portrait)" href="/images/splash/iphone-12.png">
        {{-- iPhone 12 Pro Max / 13 Pro Max / 14 Plus --}}
        <link rel="apple-touch-startup-image" media="(device-width:428px) and (device-height:926px) and (-webkit-device-pixel-ratio:3) and (orientation:portrait)" href="/images/splash/iphone-12-max.png">
        {{-- iPhone 14 Pro --}}
        <link rel="apple-touch-startup-image" media="(device-width:393px) and (device-height:852px) and (-webkit-device-pixel-ratio:3) and (orientation:portrait)" href="/images/splash/iphone-14-pro.png">
        {{-- iPhone 14 Pro Max --}}
        <link rel="apple-touch-startup-image" media="(device-width:430px) and (device-height:932px) and (-webkit-device-pixel-ratio:3) and (orientation:portrait)" href="/images/splash/iphone-14-max.png">
        {{-- iPad --}}
        <link rel="apple-touch-startup-image" media="(device-width:768px) and (device-height:1024px) and (-webkit-device-pixel-ratio:2) and (orientation:portrait)" href="/images/splash/ipad.png">
        {{-- iPad Pro 11" --}}
        <link rel="apple-touch-startup-image" media="(device-width:834px) and (device-height:1194px) and (-webkit-device-pixel-ratio:2) and (orientation:portrait)" href="/images/splash/ipad-pro-11.png">
        {{-- iPad Pro 12.9" --}}
        <link rel="apple-touch-startup-image" media="(device-width:1024px) and (device-height:1366px) and (-webkit-device-pixel-ratio:2) and (orientation:portrait)" href="/images/splash/ipad-pro-129.png">

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

            // Clean up any old sidebar storage that could cause issues
            try { localStorage.removeItem('sidebarCollapsed'); sessionStorage.removeItem('sidebarCollapsed'); } catch(e) {}

            // Sidebar store — collapsed is always false on load (inline style handles pre-Alpine width)
            document.addEventListener('alpine:init', function () {
                Alpine.store('sidebar', {
                    collapsed: false,
                    mobileOpen: false,
                    isDesktop: window.innerWidth >= 1024,
                    toggle: function () { this.collapsed = !this.collapsed; },
                    openMobile: function () { this.mobileOpen = true; },
                    closeMobile: function () { this.mobileOpen = false; }
                });

                window.addEventListener('resize', function () {
                    var store = Alpine.store('sidebar');
                    var wasDesktop = store.isDesktop;
                    store.isDesktop = window.innerWidth >= 1024;
                    if (store.isDesktop) store.mobileOpen = false;
                    if (!store.isDesktop && wasDesktop) store.mobileOpen = false;
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
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
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

            {{-- Main panel: sm:ml-64 is the CSS default (expanded); Alpine style binding overrides on toggle --}}
            <div
                class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
                style="margin-left:0"
                :style="{ marginLeft: $store.sidebar.isDesktop ? ($store.sidebar.collapsed ? '4.5rem' : '16rem') : '0' }"
            >
                {{-- ── Top header bar ── --}}
                @if (isset($header))
                    <header class="sticky top-0 z-30 bg-white dark:bg-slate-950 border-b border-gray-200 dark:border-slate-800">
                        <div class="flex items-center justify-between h-14 px-4 sm:px-6">

                            {{-- Hamburger (mobile) + Page title --}}
                            <div class="flex items-center gap-3">
                                {{-- Hamburger — mobile only --}}
                                {{-- Hamburger: taps to open the full sidebar drawer on mobile --}}
                                <button
                                    class="lg:hidden flex items-center justify-center w-11 h-11 rounded-xl text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 transition active:scale-95"
                                    @click="$store.sidebar.openMobile()"
                                    aria-label="Open menu">
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

                                {{-- User avatar + logout dropdown --}}
                                @auth
                                    <livewire:layout.header-user />
                                @endauth
                            </div>
                        </div>
                    </header>
                @endif

                {{-- ── Page content ── --}}
                <main class="flex-1 p-4 sm:p-6 mobile-content-pb">
                    {{ $slot }}
                </main>

                {{-- ── Footer ── --}}
                <footer class="px-6 py-3 border-t border-gray-200 dark:border-slate-800 text-xs text-gray-400 dark:text-slate-600">
                    &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; All rights reserved.
                </footer>

            </div>
        </div>

        @include('layouts._mobile-nav')
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
