<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDrop | Contact</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    {{-- Load the locally built Tailwind utilities before the shared component theme. --}}
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">

@php
    // Shared input style so every field looks the same
    $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
@endphp

    <!-- TOP NAVIGATION BAR (public pages): same white bar as the landing page -->
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-2 text-lg font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-400 text-slate-900"><i data-lucide="droplet" class="h-4 w-4"></i></span>
                FuelDrop
            </a>
            <nav class="flex flex-wrap items-center gap-5 text-sm" aria-label="Main navigation">
                <a href="{{ route('landing') }}#how" class="font-medium text-slate-600 transition hover:text-slate-900">How it works</a>
                <a href="{{ route('landing') }}#fuels" class="font-medium text-slate-600 transition hover:text-slate-900">Fuels</a>
                <!-- Current page -->
                <a href="{{ route('contact') }}" class="font-medium text-slate-900" aria-current="page">Contact</a>
                <a href="{{ route('login') }}" class="font-medium text-slate-600 transition hover:text-slate-900">Sign in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-amber-400 px-4 py-2 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Get started</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-xl px-6 py-12">

        <!-- Page heading -->
        <h1 class="text-3xl font-bold">Contact us</h1>

        <!-- Success message after sending -->
        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <!-- Error display: first validation error -->
        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <!-- Contact form: same route and field names as before -->
        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
            @csrf

            <!-- Fields -->
            <div class="space-y-5 p-6">
                <label class="block text-sm font-medium">Name
                    <input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Email
                    <input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Message
                    <textarea name="message" rows="5" required maxlength="5000" class="{{ $input }}">{{ old('message') }}</textarea>
                </label>
            </div>

            <!-- Action -->
            <div class="flex items-center justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Send message</button>
            </div>
        </form>
    </main>

    <!-- Render Lucide icons -->
    <script>lucide.createIcons();</script>
</body>
</html>
