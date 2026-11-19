<?php

namespace App\Http\Controllers;

use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Provider;
use App\Models\StockReservation;
use App\Models\Truck;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProviderOrderController extends Controller
{
    public function index(Request $request, Provider $provider): View
    {
        $this->authorize('view', $provider);

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $orders = $provider->orders()
            ->with(['customer', 'driver.user', 'truck', 'payment'])
            ->searchReference($search)
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return view('provider.orders.index', compact('provider', 'orders', 'search'));
    }

    public function show(Provider $provider, Order $order): View
    {
        $this->authorize('view', $provider);
        abort_unless($order->provider_id === $provider->id, 404);

        $totalFuelLitres = (float) $order->fuels()->sum('fuel_order.quantity_litres');
        $order->load([
            'customer',
            'driver.user',
            'truck',
            'payment',
            'fuels',
            'stockReservations.branchFuel.branch',
            'stockReservations.branchFuel.fuel',
        ]);
        $drivers = $provider->drivers()
            ->where(function ($query) use ($order): void {
                $query->where('available_status', 'available');

                if ($order->driver_id !== null) {
                    $query->orWhereKey($order->driver_id);
                }
            })
            ->when($order->branch_id !== null, fn ($query) => $query->where(function ($branchQuery) use ($order): void {
                $branchQuery->whereNull('branch_id')->orWhere('branch_id', $order->branch_id);
            }))
            ->with('user')
            ->orderBy('id')
            ->get();
        $trucks = $provider->trucks()
            ->where(function ($query) use ($order): void {
                $query->where('status', 'available');

                if ($order->truck_id !== null) {
                    $query->orWhereKey($order->truck_id);
                }
            })
            ->when($order->branch_id !== null, fn ($query) => $query->where(function ($branchQuery) use ($order): void {
                $branchQuery->whereNull('branch_id')->orWhere('branch_id', $order->branch_id);
            }))
            ->where(function ($query) use ($totalFuelLitres): void {
                $query->whereNull('capacity_litres')
                    ->orWhere('capacity_litres', '>=', $totalFuelLitres);
            })
            ->orderBy('name')
            ->get();
        $latestLocation = $order->locations()->latest('recorded_at')->first();

        return view('provider.orders.show', compact('provider', 'order', 'drivers', 'trucks', 'latestLocation'));
    }

    public function accept(Request $request, Provider $provider, Order $order): RedirectResponse
    {
        $this->authorize('manageOrders', $provider);
        abort_unless($order->provider_id === $provider->id, 404);

        DB::transaction(function () use ($request, $provider, $order): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages([
                    'order' => 'Only pending orders can be accepted.',
                ]);
            }

            $lockedOrder->load('fuels');

            foreach ($lockedOrder->fuels as $fuel) {
                $branchFuel = BranchFuel::query()
                    ->whereKey($fuel->pivot->branch_fuel_id)
                    ->with('branch')
                    ->lockForUpdate()
                    ->firstOrFail();
                $quantity = (float) $fuel->pivot->quantity_litres;

                if ($branchFuel->branch->provider_id !== $lockedOrder->provider_id
                    || ($lockedOrder->branch_id !== null && $branchFuel->branch_id !== $lockedOrder->branch_id)) {
                    throw ValidationException::withMessages([
                        'order' => 'The order inventory does not belong to this provider and branch.',
                    ]);
                }

                if ((float) $branchFuel->available_stock_litres - (float) $branchFuel->reserved_stock_litres < $quantity) {
                    throw ValidationException::withMessages([
                        'order' => 'There is not enough unreserved stock to accept this order.',
                    ]);
                }

                $branchFuel->increment('reserved_stock_litres', $quantity);

                StockReservation::create([
                    'order_id' => $lockedOrder->id,
                    'branch_fuel_id' => $branchFuel->id,
                    'quantity_litres' => $quantity,
                    'status' => 'reserved',
                    'reserved_at' => now(),
                ]);
            }

            $lockedOrder->update(['status' => 'accepted']);
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => 'accepted',
                'changed_by' => $request->user()->id,
            ]);

            UserNotification::create([
                'user_id' => $lockedOrder->user_id,
                'order_id' => $lockedOrder->id,
                'title' => 'Your provider accepted the order',
            ]);
            $provider->notifyActiveMembers(
                'Order '.$lockedOrder->order_number.' was accepted',
                $lockedOrder,
                $request->user(),
            );
        });

        return to_route('provider.providers.orders.show', [$provider, $order])
            ->with('status', 'Order accepted. Assign a driver and truck when ready.');
    }

    public function reject(Request $request, Provider $provider, Order $order): RedirectResponse
    {
        $this->authorize('manageOrders', $provider);
        abort_unless($order->provider_id === $provider->id, 404);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $provider, $order, $validated): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages([
                    'order' => 'Only pending orders can be rejected.',
                ]);
            }

            $lockedOrder->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => 'rejected',
                'changed_by' => $request->user()->id,
            ]);

            UserNotification::create([
                'user_id' => $lockedOrder->user_id,
                'order_id' => $lockedOrder->id,
                'title' => 'Your provider rejected the order',
            ]);
            $provider->notifyActiveMembers(
                'Order '.$lockedOrder->order_number.' was rejected',
                $lockedOrder,
                $request->user(),
            );
        });

        return to_route('provider.providers.orders.index', $provider)
            ->with('status', 'Order rejected.');
    }

    public function assign(Request $request, Provider $provider, Order $order): RedirectResponse
    {
        $this->authorize('assignDeliveries', $provider);
        abort_unless($order->provider_id === $provider->id, 404);

        $validated = $request->validate([
            'driver_id' => [
                'required',
                Rule::exists('drivers', 'id')->where('provider_id', $provider->id),
            ],
            'truck_id' => [
                'required',
                Rule::exists('trucks', 'id')->where('provider_id', $provider->id),
            ],
        ]);

        $assignmentChanged = DB::transaction(function () use ($request, $provider, $order, $validated): bool {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedOrder->status, ['accepted', 'assigned'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Assign resources only to accepted orders that have not started fuel loading.',
                ]);
            }

            $driverIds = array_values(array_unique(array_filter([
                $lockedOrder->driver_id,
                (int) $validated['driver_id'],
            ])));
            $drivers = Driver::query()
                ->whereIn('id', $driverIds)
                ->where('provider_id', $provider->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $driver = $drivers->get((int) $validated['driver_id']);

            if ($driver === null) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Select a driver belonging to this provider.',
                ]);
            }

            $previousDriver = $lockedOrder->driver_id === null ? null : $drivers->get($lockedOrder->driver_id);
            if ($lockedOrder->driver_id !== null && $previousDriver === null) {
                abort(404);
            }

            $truckIds = array_values(array_unique(array_filter([
                $lockedOrder->truck_id,
                (int) $validated['truck_id'],
            ])));
            $trucks = Truck::query()
                ->whereIn('id', $truckIds)
                ->where('provider_id', $provider->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $truck = $trucks->get((int) $validated['truck_id']);

            if ($truck === null) {
                throw ValidationException::withMessages([
                    'truck_id' => 'Select a truck belonging to this provider.',
                ]);
            }

            $previousTruck = $lockedOrder->truck_id === null ? null : $trucks->get($lockedOrder->truck_id);
            if ($lockedOrder->truck_id !== null && $previousTruck === null) {
                abort(404);
            }

            $driverIsCurrent = $lockedOrder->driver_id === $driver->id;
            $truckIsCurrent = $lockedOrder->truck_id === $truck->id;
            if (($driverIsCurrent && $driver->available_status !== 'assigned')
                || (! $driverIsCurrent && $driver->available_status !== 'available')) {
                throw ValidationException::withMessages([
                    'driver_id' => 'The selected driver is no longer available.',
                ]);
            }

            if (($truckIsCurrent && $truck->status !== 'assigned')
                || (! $truckIsCurrent && $truck->status !== 'available')) {
                throw ValidationException::withMessages([
                    'truck_id' => 'The selected truck is no longer available.',
                ]);
            }

            if (($driver->branch_id !== null && $lockedOrder->branch_id !== null && $driver->branch_id !== $lockedOrder->branch_id)
                || ($truck->branch_id !== null && $lockedOrder->branch_id !== null && $truck->branch_id !== $lockedOrder->branch_id)) {
                throw ValidationException::withMessages([
                    'driver_id' => 'The selected driver and truck must serve the order branch.',
                ]);
            }

            $requiredCapacity = (float) $lockedOrder->fuels()->sum('fuel_order.quantity_litres');
            if ($truck->capacity_litres !== null && (float) $truck->capacity_litres < $requiredCapacity) {
                throw ValidationException::withMessages([
                    'truck_id' => 'The selected truck does not have enough capacity for this order.',
                ]);
            }

            $activeStatuses = ['assigned', 'fuel_loading', 'out_for_delivery'];
            if (Order::query()
                ->whereIn('driver_id', $driverIds)
                ->where('id', '!=', $lockedOrder->id)
                ->whereIn('status', $activeStatuses)
                ->exists()) {
                throw ValidationException::withMessages([
                    'driver_id' => 'The selected driver has another active delivery.',
                ]);
            }

            if (Order::query()
                ->whereIn('truck_id', $truckIds)
                ->where('id', '!=', $lockedOrder->id)
                ->whereIn('status', $activeStatuses)
                ->exists()) {
                throw ValidationException::withMessages([
                    'truck_id' => 'The selected truck has another active delivery.',
                ]);
            }

            $wasAccepted = $lockedOrder->status === 'accepted';
            $assignmentChanged = $wasAccepted
                || ! $driverIsCurrent
                || ! $truckIsCurrent;

            if ($previousDriver !== null && ! $driverIsCurrent) {
                $previousDriver->update(['available_status' => 'available']);
            }
            if ($previousTruck !== null && ! $truckIsCurrent) {
                $previousTruck->update(['status' => 'available']);
            }

            $lockedOrder->update([
                'driver_id' => $driver->id,
                'truck_id' => $truck->id,
                'status' => 'assigned',
            ]);
            if (! $driverIsCurrent) {
                $driver->update(['available_status' => 'assigned']);
            }
            if (! $truckIsCurrent) {
                $truck->update(['status' => 'assigned']);
            }

            if ($assignmentChanged) {
                OrderStatusHistory::create([
                    'order_id' => $lockedOrder->id,
                    'status' => 'assigned',
                    'changed_by' => $request->user()->id,
                ]);
                UserNotification::create([
                    'user_id' => $lockedOrder->user_id,
                    'order_id' => $lockedOrder->id,
                    'title' => $wasAccepted
                        ? 'A driver has been assigned to your order'
                        : 'Your delivery assignment has been updated',
                ]);
                $provider->notifyActiveMembers(
                    ($wasAccepted ? 'Delivery assigned for order ' : 'Delivery assignment updated for order ')
                        .$lockedOrder->order_number,
                    $lockedOrder,
                    $request->user(),
                );
            }

            return $assignmentChanged;
        });

        return to_route('provider.providers.orders.show', [$provider, $order])
            ->with('status', $assignmentChanged ? 'Driver and truck assigned.' : 'The existing assignment is unchanged.');
    }
}
