<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProviderAccessController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $providers = Provider::query()
            ->accessibleBy($user)
            ->withCount([
                'branches',
                'drivers',
                'trucks',
                'orders',
                'orders as incoming_orders_count' => fn ($query) => $query->where('status', 'pending'),
                'orders as active_orders_count' => fn ($query) => $query
                    ->whereIn('status', ['accepted', 'assigned', 'fuel_loading', 'out_for_delivery']),
            ])
            ->orderBy('name')
            ->get();

        $recentOrders = Order::query()
            ->accessibleBy($user)
            ->with(['provider', 'payment'])
            ->latest('placed_at')
            ->limit(5)
            ->get();

        $stats = [
            'providers' => $providers->count(),
            'branches' => $providers->sum('branches_count'),
            'drivers' => $providers->sum('drivers_count'),
            'orders' => $providers->sum('orders_count'),
            'incoming_orders' => $providers->sum('incoming_orders_count'),
            'active_orders' => $providers->sum('active_orders_count'),
        ];

        return view('provider.dashboard', compact('providers', 'recentOrders', 'stats'));
    }

    public function platformDashboard(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return view('admin.platform-dashboard', [
            'providersCount' => $user->can('manage providers') ? Provider::query()->count() : null,
            'branchesCount' => $user->can('view all branches') ? Branch::query()->count() : null,
            'customersCount' => $user->can('view all customers')
                ? User::query()->whereHas('roles', fn ($query) => $query->where('name', 'customer'))->count()
                : null,
            'driversCount' => $user->can('view all drivers')
                ? User::query()->whereHas('roles', fn ($query) => $query->where('name', 'driver'))->count()
                : null,
            'ordersCount' => $user->can('view all orders') ? Order::query()->count() : null,
            'paymentsCount' => $user->can('view all payments') ? Payment::query()->count() : null,
        ]);
    }

    public function show(Provider $provider): View
    {
        $this->authorize('view', $provider);

        $provider->load('designatedAdmin')
            ->loadCount(['branches', 'branchFuel', 'drivers', 'trucks', 'orders']);

        return view('admin.providers.show', compact('provider'));
    }
}
