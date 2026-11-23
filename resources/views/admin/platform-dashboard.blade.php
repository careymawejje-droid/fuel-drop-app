<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDrop | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Share the FuelDrop palette with the standalone admin dashboard. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Use DM Sans everywhere (Tailwind's font-sans would otherwise ignore the loaded font) */
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">

@php
    // ---------------------------------------------------------------
    // Permission checks (same rules as before, evaluated once here)
    // ---------------------------------------------------------------
    $user = auth()->user();

    // Stat strip: label, count, permission, and the page each stat links to (null = no link)
    $stats = [
        ['label' => 'Providers',  'value' => $providersCount,  'can' => $user->can('manage providers'),   'route' => 'admin.providers.index'],
        ['label' => 'Branches',   'value' => $branchesCount,   'can' => $user->can('view all branches'),  'route' => 'admin.branches.index'],
        ['label' => 'Customers',  'value' => $customersCount,  'can' => $user->can('view all customers'), 'route' => null],
        ['label' => 'Drivers',    'value' => $driversCount,    'can' => $user->can('view all drivers'),   'route' => null],
        ['label' => 'Orders',     'value' => $ordersCount,     'can' => $user->can('view all orders'),    'route' => 'admin.orders.index'],
        ['label' => 'Payments',   'value' => $paymentsCount,   'can' => $user->can('view all payments'),  'route' => 'admin.payments.index'],
    ];

    // Navigation + management links, shown in the sidebar and in the main list.
    // 'group' decides which section of the sidebar the link sits under.
    $links = [
        ['group' => 'Operations', 'title' => 'Orders',    'icon' => 'clipboard-list', 'route' => 'admin.orders.index',    'desc' => 'Monitor order status and provider operations.',
            'can' => $user->can('view all orders')],
        ['group' => 'Operations', 'title' => 'Payments',  'icon' => 'wallet',         'route' => 'admin.payments.index',  'desc' => 'Review payment status and cash collections.',
            'can' => $user->can('view all payments')],
        ['group' => 'Network',    'title' => 'Providers', 'icon' => 'building-2',     'route' => 'admin.providers.index', 'desc' => 'Create providers, manage their designated accounts, and update status.',
            'can' => $user->can('manage providers')],
        ['group' => 'Network',    'title' => 'Branches',  'icon' => 'git-branch',     'route' => 'admin.branches.index',  'desc' => 'Review every branch and open its provider workspace.',
            'can' => $user->can('view all branches')],
        ['group' => 'People',     'title' => 'Platform users', 'icon' => 'users',     'route' => 'admin.users.index',     'desc' => 'Review customer, provider, driver, and admin accounts.',
            'can' => $user->can('manage users') && $user->can('manage platform users') && $user->can('view all customers') && $user->can('view all drivers')],
        ['group' => 'Platform',   'title' => 'Reports',   'icon' => 'chart-no-axes-column-increasing', 'route' => 'admin.reports.index', 'desc' => 'View platform-wide order, payment, and provider summaries.',
            'can' => $user->can('view platform reports') && $user->can('view all customers') && $user->can('view all drivers')],
        ['group' => 'Platform',   'title' => 'Settings',  'icon' => 'settings',       'route' => 'admin.settings.edit',   'desc' => 'Update support contact details and the customer delivery fee.',
            'can' => $user->can('manage platform settings')],
    ];

    // Keep only what this admin may see, and group links for the sidebar
    $stats = array_filter($stats, fn ($s) => $s['can']);
    $links = array_filter($links, fn ($l) => $l['can']);
    $groupedLinks = collect($links)->groupBy('group');
@endphp

@include('components.fueldrop.sidebar', ['role' => 'admin'])
<div class="min-h-screen lg:ml-64">
    <header class="hidden border-b border-slate-200 bg-white px-6 py-4 lg:block">
        <p class="text-sm text-slate-500">Signed in as <span class="font-medium text-slate-900">{{ $user->name }}</span></p>
    </header>

        <main class="mx-auto max-w-5xl px-6 py-8">

            <!-- Page heading -->
            <div>
                <h1 class="text-3xl font-bold">Admin dashboard</h1>
                <p class="mt-1 max-w-2xl text-slate-600">Manage FuelDrop providers and monitor platform-wide operations.</p>
            </div>

            <!-- Platform totals: one connected strip with dividers instead of six separate cards.
                 Values come from the controller; each stat links to its page when one exists. -->
            @if (count($stats))
                <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white" aria-label="Platform totals">
                    <dl class="grid grid-cols-2 divide-x divide-y divide-slate-200 sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0">
                        @foreach ($stats as $stat)
                            @php $tag = $stat['route'] ? 'a' : 'div'; @endphp
                            <{{ $tag }} @if ($stat['route']) href="{{ route($stat['route']) }}" @endif
                                class="block px-5 py-4 {{ $stat['route'] ? 'transition hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-400' : '' }}">
                                <dt class="text-sm text-slate-500">{{ $stat['label'] }}</dt>
                                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($stat['value']) }}</dd>
                            </{{ $tag }}>
                        @endforeach
                    </dl>
                </section>
            @endif

            <!-- Management links: one row per area with an icon, description and arrow -->
            <section class="mt-8" aria-label="Manage the platform">
                <h2 class="text-lg font-bold">Manage</h2>

                @if (count($links))
                    <ul class="mt-3 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        @foreach ($links as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" class="group flex items-center gap-4 px-5 py-4 transition hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-400">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition group-hover:bg-amber-100 group-hover:text-amber-700">
                                        <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold">{{ $item['title'] }}</span>
                                        <span class="block text-sm text-slate-500">{{ $item['desc'] }}</span>
                                    </span>
                                    <i data-lucide="chevron-right" class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-amber-600"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <!-- Empty state: shown when the account has no admin permissions -->
                    <p class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                        Your account has no admin permissions yet. Ask a platform administrator to assign a role.
                    </p>
                @endif
            </section>
        </main>
</div>

<!-- Render Lucide icons -->
<script>lucide.createIcons();</script>
</body>
</html>
