<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Portal') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">

        <div class="flex min-h-screen">
            {{-- Sidebar (Livewire Volt component) --}}
            <livewire:layout.sidebar />

            {{-- Main content area — offset by sidebar width --}}
            <div class="flex-1 flex flex-col ml-0 sm:ml-64">

                {{-- Page Heading --}}
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="px-6 py-5 flex items-center justify-between">
                            {{ $header }}
                            <livewire:notification-bell />
                        </div>
                    </header>
                @endif

                {{-- Page Content --}}
                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

    </body>
</html>
