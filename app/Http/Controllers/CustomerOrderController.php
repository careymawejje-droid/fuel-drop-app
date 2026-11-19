<?php

namespace App\Http\Controllers;

use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Fuel;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PlatformSetting;
use App\Models\Provider;
use App\Models\StockReservation;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\UgandaCoordinates;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $offerings = BranchFuel::query()
            ->where('status', 'active')
            ->whereRaw('(available_stock_litres - reserved_stock_litres) >= CAST(? AS DECIMAL(12, 3))', [3])
            ->whereHas('fuel', fn (Builder $query) => $query
                ->where('status', 'available')
                ->whereIn('name', ['Petrol', 'Diesel']))
            ->whereHas('branch', fn (Builder $query) => $query
                ->where('status', 'active')
                ->whereHas('provider', fn (Builder $providerQuery) => $providerQuery
                    ->where('status', 'active')))
            ->with(['branch.provider', 'fuel'])
            ->orderBy('price_per_litre')
            ->get()
            ->map(fn (BranchFuel $branchFuel): array => [
                'provider_id' => $branchFuel->branch->provider_id,
                'provider_name' => $branchFuel->branch->provider->name,
                'fuel_id' => $branchFuel->fuel_id,
                'fuel_name' => $branchFuel->fuel->name,
                'price' => (float) $branchFuel->price_per_litre,
                'available_stock' => (float) $branchFuel->available_stock_litres,
            ]);

        return view('orders.create', [
            'offerings' => $offerings,
            'vehicles' => $user->vehicles()->orderBy('id')->get(),
            'deliveryFee' => (float) PlatformSetting::current()->delivery_fee,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'provider_id' => [
                'required',
                Rule::exists('providers', 'id')->where('status', 'active'),
            ],
            'fuel_id' => [
                'required',
                Rule::exists('fuels', 'id')->where('status', 'available'),
            ],
            'quantity' => ['required', 'numeric', 'between:3,10', 'decimal:0,3'],
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where('user_id', $user->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:60'],
            'locality' => ['nullable', 'string', 'max:60'],
            'area' => ['nullable', 'string', 'max:70'],
            'road' => ['nullable', 'string', 'max:70'],
            'landmark' => ['nullable', 'string', 'max:90'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_confirmed' => ['accepted'],
        ]);

        if (! UgandaCoordinates::contains((float) $validated['latitude'], (float) $validated['longitude'])) {
            throw ValidationException::withMessages([
                'latitude' => 'Choose a delivery pin within Uganda. You can adjust the pin on the map.',
            ]);
        }

        if (! Fuel::query()
            ->whereKey($validated['fuel_id'])
            ->whereIn('name', ['Petrol', 'Diesel'])
            ->where('status', 'available')
            ->exists()) {
            throw ValidationException::withMessages([
                'fuel_id' => 'Only available Petrol or Diesel can be ordered.',
            ]);
        }

        $quantity = (float) $validated['quantity'];
        $order = DB::transaction(function () use ($validated, $user, $quantity): Order {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($user->orders()->whereNotIn('status', ['delivered', 'completed', 'rejected', 'cancelled'])->exists()) {
                throw ValidationException::withMessages([
                    'provider_id' => 'You already have an active order.',
                ]);
            }

            $branchFuel = BranchFuel::query()
                ->where('fuel_id', $validated['fuel_id'])
                ->where('status', 'active')
                ->whereRaw('(available_stock_litres - reserved_stock_litres) >= CAST(? AS DECIMAL(12, 3))', [$quantity])
                ->whereHas('branch', fn (Builder $branchQuery) => $branchQuery
                    ->where('provider_id', $validated['provider_id'])
                    ->where('status', 'active')
                    ->whereHas('provider', fn (Builder $providerQuery) => $providerQuery
                        ->where('status', 'active')))
                ->with(['branch.provider', 'fuel'])
                ->lockForUpdate()
                ->first();

            if ($branchFuel === null) {
                throw ValidationException::withMessages([
                    'provider_id' => 'That provider cannot fulfil the selected fuel and quantity.',
                ]);
            }

            $subtotal = round((float) $branchFuel->price_per_litre * $quantity, 2);
            $deliveryFee = (float) PlatformSetting::current()->delivery_fee;
            $total = $subtotal + $deliveryFee;
            $deliveryAddress = filled($validated['district'] ?? null)
                ? collect([
                    'Uganda',
                    'District: '.$validated['district'],
                    filled($validated['locality'] ?? null) ? 'Town/City: '.$validated['locality'] : null,
                    filled($validated['area'] ?? null) ? 'Area: '.$validated['area'] : null,
                    filled($validated['road'] ?? null) ? 'Road: '.$validated['road'] : null,
                    filled($validated['landmark'] ?? null) ? 'Landmark/Directions: '.$validated['landmark'] : null,
                ])->filter()->implode(', ')
                : trim((string) ($validated['address'] ?? ''));

            if (! filled($validated['district'] ?? null) && filled($validated['landmark'] ?? null)) {
                $deliveryAddress .= ' - Landmark: '.$validated['landmark'];
            }

            if ($deliveryAddress === '' || mb_strlen($deliveryAddress) > 255) {
                throw ValidationException::withMessages([
                    'district' => 'Enter a delivery address that fits within 255 characters.',
                ]);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'provider_id' => $branchFuel->branch->provider_id,
                'branch_id' => $branchFuel->branch_id,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'total' => $total,
                'delivery_address' => $deliveryAddress,
                'delivery_latitude' => $validated['latitude'] ?? null,
                'delivery_longitude' => $validated['longitude'] ?? null,
                'status' => 'pending',
                'placed_at' => now(),
            ]);

            $order->fuels()->attach($branchFuel->fuel_id, [
                'branch_fuel_id' => $branchFuel->id,
                'quantity_litres' => $quantity,
                'unit_price' => $branchFuel->price_per_litre,
            ]);

            $order->payments()->create([
                'payment_method' => 'cash_on_delivery',
                'amount' => $total,
                'status' => 'pending',
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'changed_by' => $user->id,
            ]);

            $branchFuel->branch->provider->notifyActiveMembers('New order awaiting provider response', $order);

            return $order;
        });

        return redirect()->route('customer.orders.confirmation', $order);
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $orders = $user->orders()
            ->with(['fuels', 'payment'])
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('customer.dashboard', [
            'orders' => $orders,
            'ordersCount' => $user->orders()->count(),
            'pendingOrdersCount' => $user->orders()->where('status', 'pending')->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $orders = $user->orders()
            ->with(['fuels', 'payment'])
            ->searchReference($search)
            ->latest('placed_at')
            ->paginate(10)
            ->withQueryString();

        return view('customer.orders.index', compact('orders', 'search'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['provider', 'driver.user', 'fuels', 'payment']);

        return view('customer.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        DB::transaction(function () use ($order, $user): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->user_id !== $user->id
                || ! in_array($lockedOrder->status, ['pending', 'accepted', 'assigned'], true)) {
                abort(403);
            }

            $reservations = StockReservation::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', 'reserved')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $branchFuel = BranchFuel::query()
                    ->whereKey($reservation->branch_fuel_id)
                    ->whereHas('branch', fn (Builder $query) => $query
                        ->where('provider_id', $lockedOrder->provider_id)
                        ->when($lockedOrder->branch_id !== null, fn (Builder $branchQuery) => $branchQuery
                            ->whereKey($lockedOrder->branch_id)))
                    ->lockForUpdate()
                    ->firstOrFail();
                $quantity = (float) $reservation->quantity_litres;

                if ((float) $branchFuel->reserved_stock_litres < $quantity) {
                    throw ValidationException::withMessages([
                        'order' => 'Reserved inventory could not be reconciled for this cancellation.',
                    ]);
                }

                $branchFuel->decrement('reserved_stock_litres', $quantity);
                $reservation->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);
            }

            if ($lockedOrder->driver_id !== null) {
                $driver = Driver::query()
                    ->whereKey($lockedOrder->driver_id)
                    ->where('provider_id', $lockedOrder->provider_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (Order::query()
                    ->where('driver_id', $driver->id)
                    ->where('id', '!=', $lockedOrder->id)
                    ->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'order' => 'The assigned driver has another active delivery.',
                    ]);
                }

                $driver->update(['available_status' => 'available']);
            }

            if ($lockedOrder->truck_id !== null) {
                $truck = Truck::query()
                    ->whereKey($lockedOrder->truck_id)
                    ->where('provider_id', $lockedOrder->provider_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (Order::query()
                    ->where('truck_id', $truck->id)
                    ->where('id', '!=', $lockedOrder->id)
                    ->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'order' => 'The assigned truck has another active delivery.',
                    ]);
                }

                $truck->update(['status' => 'available']);
            }

            $lockedOrder->update([
                'status' => 'cancelled',
                'driver_id' => null,
                'truck_id' => null,
            ]);
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => 'cancelled',
                'changed_by' => $user->id,
            ]);
            UserNotification::create([
                'user_id' => $user->id,
                'order_id' => $lockedOrder->id,
                'title' => 'Your order was cancelled',
            ]);

            if ($lockedOrder->provider_id !== null) {
                Provider::query()->findOrFail($lockedOrder->provider_id)
                    ->notifyActiveMembers('Customer cancelled order '.$lockedOrder->order_number, $lockedOrder);
            }
        });

        return to_route('customer.orders.show', $order)
            ->with('status', 'Order cancelled.');
    }

    public function confirmation(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['provider', 'fuels', 'payment']);

        return view('orders.confirmation', compact('order'));
    }

    public function payment(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load('payment');

        return view('orders.payment', compact('order'));
    }

    public function confirmDelivery(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('confirmDelivery', $order);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        DB::transaction(function () use ($order, $user): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->user_id !== $user->id || $lockedOrder->status !== 'out_for_delivery') {
                abort(403);
            }

            $payments = $lockedOrder->payments()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($payments->count() !== 1) {
                abort(403);
            }

            $payment = $payments->first();

            if ($payment->payment_method !== 'cash_on_delivery'
                || $payment->status !== 'paid'
                || $payment->collected_at === null
                || $payment->collected_by_driver_id !== $lockedOrder->driver_id
                || number_format((float) $payment->amount, 2, '.', '')
                    !== number_format((float) $lockedOrder->total, 2, '.', '')) {
                abort(403);
            }

            $lockedOrder->update(['status' => 'delivered']);

            if ($lockedOrder->driver_id !== null) {
                Driver::query()
                    ->whereKey($lockedOrder->driver_id)
                    ->where('provider_id', $lockedOrder->provider_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->update(['available_status' => 'available']);
            }

            if ($lockedOrder->truck_id !== null) {
                Truck::query()
                    ->whereKey($lockedOrder->truck_id)
                    ->where('provider_id', $lockedOrder->provider_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->update(['status' => 'available']);
            }

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => 'delivered',
                'changed_by' => $user->id,
            ]);

            $lockedOrder->provider?->notifyActiveMembers('Customer confirmed delivery', $lockedOrder);
        });

        return to_route('customer.orders.show', $order)
            ->with('status', 'Delivery confirmed.');
    }
}
