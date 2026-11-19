<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FuelDrop | Sign up</title>

    {{-- Same font, Tailwind setup and icons as the dashboards --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { DEFAULT: '#0f1b2d', 800: '#1b2a42', 900: '#0f1b2d' },
                        brand: { 400: '#f7b84b', 500: '#f5a623', 600: '#d98e0f' },
                        cream: '#fff4df',
                    },
                    fontFamily: { sans: ['DM Sans', 'system-ui', '-apple-system', 'sans-serif'] },
                }
            }
        };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-navy font-sans antialiased min-h-screen flex items-center justify-center px-4 py-8">

    {{-- Sign up card --}}
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-sm p-8">

        {{-- Logo and heading --}}
        <div class="flex items-center gap-3 mb-6">
            <div class="h-12 w-12 rounded-xl bg-navy flex items-center justify-center">
                <i data-lucide="droplet" class="text-brand-500 fill-brand-500"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold">FuelDrop</h1>
                <p class="text-sm text-slate-500">Create your account</p>
            </div>
        </div>

        {{-- Register form: posts to Fortify's register route --}}
        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Full name: old('name') keeps what the user typed if validation fails --}}
            <div>
                <label for="name" class="block text-sm font-medium mb-1">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       placeholder="Your full name"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone number: type="tel" is the correct HTML type --}}
            <div>
                <label for="phone" class="block text-sm font-medium mb-1">Phone number</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
                       placeholder="07XX XXX XXX"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       placeholder="you@example.com"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium mb-1">Password</label>
                <input id="password" name="password" type="password" required
                       placeholder="********"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm password: must be named password_confirmation for Fortify's validation --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       placeholder="********"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
            </div>

            {{-- Submit button --}}
            <button type="submit" class="w-full rounded-lg bg-brand-500 hover:bg-brand-600 py-2.5 font-bold text-navy">
                Sign up
            </button>
        </form>

        {{-- Link to the login page --}}
        <p class="mt-6 pt-6 border-t border-slate-200 text-sm text-center text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="text-brand-600 font-bold hover:underline">Sign in</a>
        </p>
    </div>

    {{-- Turns the data-lucide placeholders into icons --}}
    <script>lucide.createIcons();</script>
</body>
</html>