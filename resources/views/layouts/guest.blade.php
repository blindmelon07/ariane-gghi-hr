<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Portal') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Dark mode: prevent flash -->
        <script>
            if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased min-h-screen">

        <div class="min-h-screen flex">

            {{-- ── Left branding panel (hidden on mobile) ── --}}
            <div class="hidden lg:flex lg:flex-col lg:w-[420px] xl:w-[480px] bg-[#0f172a] relative overflow-hidden shrink-0">

                {{-- Background decorative circles --}}
                <div class="absolute -top-20 -left-20 w-80 h-80 rounded-full bg-indigo-600/20 blur-3xl"></div>
                <div class="absolute bottom-10 right-0 w-72 h-72 rounded-full bg-indigo-800/30 blur-2xl"></div>
                <div class="absolute top-1/2 left-1/4 w-48 h-48 rounded-full bg-violet-700/20 blur-2xl"></div>

                {{-- Content --}}
                <div class="relative z-10 flex flex-col h-full px-10 py-10">

                    {{-- Logo --}}
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-bold leading-tight">{{ config('app.name', 'HR Portal') }}</p>
                            <p class="text-indigo-400 text-xs">Management System</p>
                        </div>
                    </div>

                    {{-- Hero text --}}
                    <div class="mt-auto mb-auto pt-16">
                        @if (file_exists(public_path('images/gghi logo.png')))
                            <img src="{{ asset('images/gghi logo.png') }}" alt="{{ config('app.name') }}"
                                 class="h-16 w-auto brightness-0 invert mb-8" />
                        @endif
                        <h1 class="text-3xl xl:text-4xl font-bold text-white leading-snug">
                            Your HR, <br>
                            <span class="text-indigo-400">all in one place.</span>
                        </h1>
                        <p class="mt-4 text-slate-400 text-sm leading-relaxed max-w-xs">
                            Manage attendance, payroll, leaves, and schedules — streamlined for your entire organization.
                        </p>

                        {{-- Feature pills --}}
                        <div class="mt-8 flex flex-wrap gap-2">
                            @foreach (['Attendance Tracking', 'Payroll Processing', 'Leave Management', 'BioTime Sync'] as $feature)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/8 border border-white/10 text-xs text-slate-300">
                                    <svg class="w-3 h-3 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $feature }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Footer --}}
                    <p class="text-slate-600 text-xs">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </div>
            </div>

            {{-- ── Right form panel ── --}}
            <div class="flex-1 flex flex-col items-center justify-center bg-gray-50 dark:bg-slate-950 px-6 py-12">

                {{-- Mobile logo --}}
                <div class="lg:hidden mb-8 text-center">
                    @if (file_exists(public_path('images/gghi logo.png')))
                        <img src="{{ asset('images/gghi logo.png') }}" alt="{{ config('app.name') }}"
                             class="h-14 w-auto mx-auto mb-3 dark:brightness-0 dark:invert" />
                    @else
                        <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ config('app.name') }}</h2>
                </div>

                {{-- Card --}}
                <div class="w-full max-w-md">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl dark:shadow-slate-900/50 border border-gray-200 dark:border-slate-800 px-8 py-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

        </div>

    </body>
</html>
