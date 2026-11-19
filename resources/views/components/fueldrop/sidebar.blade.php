@php
    $user = auth()->user();
    $dashboardRoute = match ($role) {
        'admin' => 'admin.dashboard',
        'provider' => 'provider.dashboard',
        'driver' => 'driver.dashboard',
        default => 'customer.dashboard',
    };

    $items = [];
    $dashboardPermission = match ($role) {
        'admin' => null,
        'provider' => 'view own orders',
        'driver' => 'view assigned deliveries',
        default => 'view own orders',
    };
    if ($role === 'admin' || $user->can($dashboardPermission)) {
        $items[] = ['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => $dashboardRoute, 'active' => $dashboardRoute];
    }

    if ($role === 'customer') {
        if ($user->can('view own orders')) {
            $items[] = ['title' => 'My orders', 'icon' => 'clipboard-list', 'route' => 'customer.orders.index', 'active' => 'customer.orders.*'];
        }
        if ($user->can('view available fuels') && $user->can('place orders')) {
            $items[] = ['title' => 'Order fuel', 'icon' => 'fuel', 'route' => 'customer.orders.create', 'active' => 'customer.orders.create'];
        }
    } elseif ($role === 'driver') {
        if ($user->can('view assigned deliveries')) {
            $items[] = ['title' => 'Deliveries', 'icon' => 'truck', 'route' => 'driver.deliveries.index', 'active' => 'driver.deliveries.*'];
        }
    } elseif ($role === 'provider') {
        if ($user->can('view own orders')) {
            $items[] = ['title' => 'Provider workspaces', 'icon' => 'building-2', 'route' => 'provider.providers.index', 'active' => 'provider.providers.*', 'exclude' => 'provider.providers.tracking.*'];
        }
        if ($user->can('view own orders')) {
            $trackingProvider = $user->providers()->wherePivot('is_active', true)->orderBy('providers.id')->first();
            if ($trackingProvider !== null) {
                $items[] = ['title' => 'Live deliveries', 'icon' => 'map-pinned', 'route' => 'provider.providers.tracking.index', 'parameters' => [$trackingProvider], 'active' => 'provider.providers.tracking.*'];
            }
        }
    } elseif ($role === 'admin') {
        if ($user->can('manage providers')) {
            $items[] = ['title' => 'Providers', 'icon' => 'building-2', 'route' => 'admin.providers.index', 'active' => 'admin.providers.*'];
        }
        if ($user->can('view all branches')) {
            $items[] = ['title' => 'Branches', 'icon' => 'git-branch', 'route' => 'admin.branches.index', 'active' => 'admin.branches.*'];
        }
        if ($user->can('view all orders')) {
            $items[] = ['title' => 'Orders', 'icon' => 'clipboard-list', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*'];
            $items[] = ['title' => 'Live deliveries', 'icon' => 'map-pinned', 'route' => 'admin.tracking.index', 'active' => 'admin.tracking.*'];
        }
        if ($user->can('view all payments')) {
            $items[] = ['title' => 'Payments', 'icon' => 'wallet', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*'];
        }
        if ($user->can('manage users')
            && $user->can('manage platform users')
            && $user->can('view all customers')
            && $user->can('view all drivers')) {
            $items[] = ['title' => 'Platform users', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'];
        }
        if ($user->can('view platform reports') && $user->can('view all customers') && $user->can('view all drivers')) {
            $items[] = ['title' => 'Reports', 'icon' => 'chart-no-axes-column-increasing', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'];
        }
        if ($user->can('manage platform settings')) {
            $items[] = ['title' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*'];
        }
    }

    if ($user->can('view own notifications')) {
        $items[] = ['title' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'active' => 'notifications.*'];
    }

    if (\Illuminate\Support\Facades\Route::has('profile.show')) {
        $items[] = ['title' => 'Profile', 'icon' => 'user-round', 'route' => 'profile.show', 'active' => 'profile.*'];
    }
@endphp

<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-slate-900 text-slate-300 lg:flex">
    <a href="{{ route($dashboardRoute) }}" class="flex items-center gap-2 px-6 py-6 text-lg font-bold text-white">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-400 text-slate-900"><i data-lucide="droplet" class="h-4 w-4"></i></span>
        FuelDrop
    </a>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="{{ ucfirst($role) }} navigation">
        @include('components.fueldrop.sidebar-links', ['items' => $items])
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-800 p-3">
        @csrf
        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
            <i data-lucide="log-out" class="h-4 w-4"></i>Sign out
        </button>
    </form>
</aside>

<header class="relative z-20 border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route($dashboardRoute) }}" class="flex items-center gap-2 font-bold text-slate-900">
            <i data-lucide="droplet" class="h-5 w-5 text-amber-500"></i>FuelDrop
        </a>
        <details class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                <i data-lucide="menu" class="h-4 w-4"></i>Menu
            </summary>
            <nav class="absolute right-0 top-full mt-2 flex w-64 flex-col gap-1 rounded-xl border border-slate-200 bg-slate-900 p-3 text-white shadow-xl" aria-label="{{ ucfirst($role) }} navigation">
                @include('components.fueldrop.sidebar-links', ['items' => $items])
                <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-slate-700 pt-2">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-300 hover:bg-slate-800 hover:text-white">
                        <i data-lucide="log-out" class="h-4 w-4"></i>Sign out
                    </button>
                </form>
            </nav>
        </details>
    </div>
</header>
