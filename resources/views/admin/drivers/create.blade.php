<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDrop | Add driver</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Use DM Sans everywhere, same as the other pages */
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">

@php
    // Shared input style so every field looks the same
    $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
@endphp

    <!-- Top bar: back link to the provider workspace -->
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
            <a href="{{ route('provider.providers.show', $provider) }}" class="flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>Back to {{ $provider->name }}
            </a>
            <span class="flex items-center gap-2 text-sm font-bold">
                <i data-lucide="fuel" class="h-4 w-4 text-amber-500"></i>FuelDrop
            </span>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-8">

        <!-- Page heading -->
        <div>
            <h1 class="text-3xl font-bold">Add driver</h1>
            <p class="mt-1 text-slate-600">Create a driver account for {{ $provider->name }}.</p>
        </div>

        <!-- Validation feedback: first error; submitted values are kept in the fields below -->
        @if ($errors->any())
            <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <!-- Driver form: same route and field names as before -->
        <form method="POST" action="{{ route('provider.providers.drivers.store', $provider) }}" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
            @csrf

            <!-- Section 1: who the driver is -->
            <div class="border-b border-slate-200 p-6">
                <h2 class="font-bold">Driver details</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                        <span class="text-sm font-medium">Full name</span>
                        <input name="name" value="{{ old('name') }}" required autocomplete="name" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Email address</span>
                        <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Phone number <span class="font-normal text-slate-500">(optional)</span></span>
                        <input name="phone" value="{{ old('phone') }}" autocomplete="tel" class="{{ $input }}">
                    </label>
                </div>
            </div>

            <!-- Section 2: sign-in password -->
            <div class="border-b border-slate-200 p-6">
                <h2 class="font-bold">Sign-in password</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium">Password</span>
                        <input name="password" type="password" required autocomplete="new-password" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Confirm password</span>
                        <input name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $input }}">
                    </label>
                </div>
            </div>

            <!-- Section 3: branch and legacy truck plate -->
            <div class="p-6">
                <h2 class="font-bold">Assignment</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium">Branch</span>
                        <select name="branch_id" class="{{ $input }}">
                            <option value="">No branch assignment yet</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Legacy truck plate <span class="font-normal text-slate-500">(optional)</span></span>
                        <input name="truck_plate" value="{{ old('truck_plate') }}" class="{{ $input }}">
                    </label>
                </div>
            </div>

            <!-- Actions: cancel returns to the provider workspace, submit creates the driver -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <a href="{{ route('provider.providers.show', $provider) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
                <button class="inline-flex items-center gap-2 rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>Create driver
                </button>
            </div>
        </form>
    </main>

    <!-- Render Lucide icons -->
    <script>lucide.createIcons();</script>
</body>
</html>