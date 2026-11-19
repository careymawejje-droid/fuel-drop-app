<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverDeliveryController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $ordersQuery = Order::query()->accessibleBy($user);
        $ordersCount = (clone $ordersQuery)->count();
        $orders = (clone $ordersQuery)
            ->with(['customer', 'truck', 'payment'])
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('driver.dashboard', compact('orders', 'ordersCount'));
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $orders = Order::query()
            ->accessibleBy($user)
            ->with(['customer', 'truck', 'payment'])
            ->searchReference($search)
            ->latest('placed_at')
            ->paginate(10)
            ->withQueryString();

        return view('driver.deliveries.index', compact('orders', 'search'));
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['customer', 'truck', 'fuels', 'payment']);

        return view('driver.deliveries.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('updateDeliveryStatus', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['fuel_loading', 'out_for_delivery'])],
        ]);

        $driver = $request->user()->driver;

        DB::transaction(function () use ($request, $order, $driver, $validated): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->driver_id !== $driver->id) {
                abort(403);
            }

            $allowedTransition = ($lockedOrder->status === 'assigned' && $validated['status'] === 'fuel_loading')
                || ($lockedOrder->status === 'fuel_loading' && $validated['status'] === 'out_for_delivery');

            if (! $allowedTransition) {
                throw ValidationException::withMessages([
                    'status' => 'This delivery status transition is not allowed.',
                ]);
            }

            if ($validated['status'] === 'fuel_loading') {
                $reservations = StockReservation::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('status', 'reserved')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($reservations->isEmpty()) {
                    throw ValidationException::withMessages([
                        'status' => 'This order has no reserved fuel to load.',
                    ]);
                }

                foreach ($reservations as $reservation) {
                    $branchFuel = $reservation->branchFuel()->lockForUpdate()->firstOrFail();
                    $quantity = (float) $reservation->quantity_litres;

                    if ((float) $branchFuel->available_stock_litres < $quantity
                        || (float) $branchFuel->reserved_stock_litres < $quantity) {
                        throw ValidationException::withMessages([
                            'status' => 'Reserved inventory could not be reconciled for this delivery.',
                        ]);
                    }

                    $branchFuel->decrement('available_stock_litres', $quantity);
                    $branchFuel->decrement('reserved_stock_litres', $quantity);
                    $reservation->update([
                        'status' => 'consumed',
                        'consumed_at' => now(),
                    ]);
                }
            }

            $lockedOrder->update(['status' => $validated['status']]);
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => $validated['status'],
                'changed_by' => $request->user()->id,
            ]);
            UserNotification::create([
                'user_id' => $lockedOrder->user_id,
                'order_id' => $lockedOrder->id,
                'title' => match ($validated['status']) {
                    'fuel_loading' => 'Fuel loading has started for your order',
                    'out_for_delivery' => 'Your order is out for delivery',
                    default => 'Your order is out for delivery',
                },
            ]);
            $lockedOrder->provider?->notifyActiveMembers(
                'Order '.$lockedOrder->order_number.' status updated: '.str_replace('_', ' ', $validated['status']),
                $lockedOrder,
            );
        });

        return to_route('driver.deliveries.show', $order)
            ->with('status', 'Delivery status updated.');
    }

    public function collectCash(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('collectPayment', $order);
        $driver = $request->user()->driver;
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($request, $order, $driver, $validated): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->driver_id !== $driver->id || $lockedOrder->status !== 'out_for_delivery') {
                abort(403);
            }

            $payments = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($payments->count() !== 1) {
                throw ValidationException::withMessages([
                    'payment' => 'This order must have exactly one payment record.',
                ]);
            }

            $payment = $payments->first();

            if ($payment->payment_method !== 'cash_on_delivery'
                || $payment->status !== 'pending'
                || $payment->collected_at !== null
                || $payment->collected_by_driver_id !== null) {
                throw ValidationException::withMessages([
                    'payment' => 'This order does not have an outstanding cash collection.',
                ]);
            }

            if (number_format((float) $payment->amount, 2, '.', '')
                !== number_format((float) $lockedOrder->total, 2, '.', '')) {
                throw ValidationException::withMessages([
                    'payment' => 'The COD amount does not match the order total.',
                ]);
            }

            if (number_format((float) $validated['amount'], 2, '.', '')
                !== number_format((float) $lockedOrder->total, 2, '.', '')) {
                throw ValidationException::withMessages([
                    'amount' => 'The collected amount must equal the order total.',
                ]);
            }

            $payment->update([
                'amount' => $validated['amount'],
                'status' => 'paid',
                'collected_by_driver_id' => $driver->id,
                'collected_at' => now(),
            ]);

            UserNotification::create([
                'user_id' => $lockedOrder->user_id,
                'order_id' => $lockedOrder->id,
                'title' => 'Your COD payment has been collected. Please confirm receipt.',
            ]);
            $lockedOrder->provider?->notifyActiveMembers(
                'COD payment collected for order '.$lockedOrder->order_number,
                $lockedOrder,
                $request->user(),
            );
        });

        return to_route('driver.deliveries.show', $order)
            ->with('status', 'Cash collection recorded.');
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->driver !== null, 403);
        $driver = $user->driver;

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_metres' => ['nullable', 'numeric', 'between:0,999999.99', 'decimal:0,2'],
            'order_id' => [
                'nullable',
                Rule::exists('orders', 'id')->where('driver_id', $driver->id),
            ],
        ]);

        if (isset($validated['order_id']) && ! Order::query()
            ->whereKey($validated['order_id'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
            ->exists()) {
            abort(403, 'Location updates are only accepted for your active deliveries.');
        }

        $location = DB::transaction(function () use ($driver, $validated) {
            $location = $driver->locations()->create([
                'order_id' => $validated['order_id'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy_metres' => $validated['accuracy_metres'] ?? null,
                'recorded_at' => now(),
            ]);

            $driver->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
            ]);

            return $location;
        });

        return response()->json(['location' => $location], 201);
    }
}
