<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- Share the palette with Vite-rendered authenticated pages. --}}
        <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased fd-vite-layout">
        <x-banner />

        @php
            $role = auth()->user()->hasRole('admin') ? 'admin' : (auth()->user()->hasRole('provider') ? 'provider' : (auth()->user()->hasRole('driver') ? 'driver' : 'customer'));
        @endphp
        <div class="min-h-screen bg-gray-100">
            @include('components.fueldrop.sidebar', ['role' => $role])
            <div class="min-h-screen lg:ml-64">
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
