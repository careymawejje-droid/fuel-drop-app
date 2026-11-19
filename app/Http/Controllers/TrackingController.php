<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function customer(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        abort_unless($request->user()->hasRole('customer') && $order->user_id === $request->user()->id, 403);

        return $this->locationResponse($order);
    }

    public function providerIndex(Request $request, Provider $provider): View
    {
        $this->authorize('view', $provider);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $orders = $provider->orders()->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
            ->searchReference($search)
            ->with(['driver.user'])->latest('placed_at')->get();
        $orders->each(function (Order $order): void {
            $order->setRelation('latestTrackingLocation', $order->locations()->latest('recorded_at')->first());
        });

        return view('provider.tracking.index', compact('provider', 'orders', 'search'));
    }

    public function provider(Request $request, Provider $provider, Order $order): JsonResponse
    {
        $this->authorize('view', $provider);
        abort_unless($order->provider_id === $provider->id, 404);
        abort_unless(in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true), 404);

        return $this->locationResponse($order);
    }

    public function providerMap(Provider $provider, Order $order): View
    {
        $this->authorize('view', $provider);
        abort_unless($order->provider_id === $provider->id && in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true), 404);
        return view('provider.tracking.show', compact('provider', 'order'));
    }

    public function adminIndex(Request $request): View
    {
        abort_unless(auth()->user()->hasRole('admin') && auth()->user()->can('view all orders'), 403);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 64);
        $orders = Order::query()->whereIn('status', ['assigned', 'fuel_loading', 'out_for_delivery'])
            ->searchReference($search)
            ->with(['provider', 'driver.user'])->latest('placed_at')->get();
        $orders->each(function (Order $order): void {
            $order->setRelation('latestTrackingLocation', $order->locations()->latest('recorded_at')->first());
        });

        return view('admin.tracking.index', compact('orders', 'search'));
    }

    public function admin(Request $request, Order $order): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin') && $request->user()->can('view all orders'), 403);
        abort_unless(in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true), 404);

        return $this->locationResponse($order);
    }

    public function adminMap(Order $order): View
    {
        abort_unless(auth()->user()->hasRole('admin') && auth()->user()->can('view all orders'), 403);
        abort_unless(in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true), 404);
        return view('admin.tracking.show', compact('order'));
    }

    private function locationResponse(Order $order): JsonResponse
    {
        $order->loadMissing('driver');
        $terminal = in_array($order->status, ['delivered', 'completed', 'cancelled', 'rejected'], true);
        $location = $terminal || $order->driver_id === null ? null : $order->locations()
            ->where('driver_id', $order->driver_id)->latest('recorded_at')->first();
        $freshForSeconds = 120;
        $age = $location?->recorded_at?->diffInSeconds(now());

        return response()->json([
            'status' => $order->status,
            'destination' => ['latitude' => $order->delivery_latitude, 'longitude' => $order->delivery_longitude],
            'driver_assigned' => $order->driver_id !== null,
            'location' => $location ? [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'accuracy_metres' => $location->accuracy_metres,
                'recorded_at' => $location->recorded_at,
            ] : null,
            'fresh' => $age !== null && $age <= $freshForSeconds,
            'age_seconds' => $age,
        ]);
    }
}
