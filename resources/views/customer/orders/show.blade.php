@extends('layouts.fueldrop')

@section('title', 'Order '.$order->order_number)

@section('back-link')
    <a href="{{ route('customer.orders.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Order history</a>
@endsection

@section('content')
    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-amber-700">Your order</p>
                <h1 class="mt-1 text-3xl font-bold">{{ $order->order_number }}</h1>
                <p class="mt-2 text-slate-600">Delivery to {{ $order->delivery_address }}</p>
            </div>
            <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800" />
        </div>
    </section>

    @if (session('status'))
        <p class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">{{ $errors->first() }}</p>
    @endif

    @if ($order->status === 'rejected' && $order->rejection_reason)
        <section class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-red-900">
            <h2 class="font-bold">Provider rejection reason</h2>
            <p class="mt-2">{{ $order->rejection_reason }}</p>
        </section>
    @endif

    <section class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Provider</p>
            <p class="mt-1 font-bold">{{ $order->provider?->name ?? 'Pending provider selection' }}</p>
            <p class="mt-1 text-sm text-slate-600">Driver: {{ $order->driver?->user?->name ?? 'Not assigned' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Amount due</p>
            <p class="mt-1 font-bold">UGX {{ number_format((float) $order->total, 2) }}</p>
            <p class="mt-1 text-sm text-slate-600">Payment: {{ $order->payment?->status === 'paid' ? 'Collected' : 'Payment Pending' }}</p>
            @if ($order->payment?->status === 'paid')
                <p class="mt-1 text-sm text-slate-600">Collected: UGX {{ number_format((float) $order->payment->amount, 2) }}</p>
                <p class="mt-1 text-sm text-slate-600">Collected at: {{ $order->payment->collected_at?->format('d M Y H:i') ?? '—' }}</p>
            @endif
            <a href="{{ route('customer.orders.payment', $order) }}" class="mt-2 inline-flex text-sm font-semibold text-blue-700">Payment details</a>
        </div>
    </section>

    @if (in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true))
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">Delivery tracking</h2>
            <p id="tracking-state" class="my-3 text-sm text-slate-600" role="status">Waiting for driver location…</p>
            <div id="delivery-map" class="z-0 h-80 w-full rounded-xl" aria-label="Delivery map"></div>
            <p id="tracking-updated" class="mt-2 text-xs text-slate-500"></p>
        </section>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (() => {
                const state = document.getElementById('tracking-state');
                const updated = document.getElementById('tracking-updated');
                const map = L.map('delivery-map').setView([0.3476, 32.5825], 9);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
                let driverMarker = null;
                let destinationMarker = null;
                let destination = null;
                let stopped = false;
                const ugandaBoundary = [[31.86617,-1.02736],[30.76986,-1.01455],[30.419105,-1.134659],[29.821519,-1.443322],[29.579466,-1.341313],[29.587838,-0.587406],[29.8195,-0.2053],[29.875779,0.59738],[30.086154,1.062313],[30.468508,1.583805],[30.85267,1.849396],[31.174149,2.204465],[30.77332,2.33989],[30.83385,3.50917],[31.24556,3.7819],[31.88145,3.55827],[32.68642,3.79232],[33.39,3.79],[34.005,4.249885],[34.47913,3.5556],[34.59607,3.05374],[35.03599,1.90584],[34.6721,1.17694],[34.18,0.515],[33.893569,0.109814],[33.903711,-0.95],[31.86617,-1.02736]];
                const isInUganda = (lat, lng) => {
                    let inside = false;
                    for (let i = 0, j = ugandaBoundary.length - 1; i < ugandaBoundary.length; j = i++) {
                        const [xi, yi] = ugandaBoundary[i], [xj, yj] = ugandaBoundary[j];
                        if ((yi > lat) !== (yj > lat) && lng < (xj - xi) * (lat - yi) / (yj - yi) + xi) inside = !inside;
                    }
                    return inside;
                };
                const validCoordinate = (latitude, longitude) => latitude !== null && latitude !== undefined && latitude !== '' && longitude !== null && longitude !== undefined && longitude !== '' && Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude)) && Number(latitude) >= -90 && Number(latitude) <= 90 && Number(longitude) >= -180 && Number(longitude) <= 180 && isInUganda(Number(latitude), Number(longitude));
                const endpoint = @json(route('customer.orders.tracking', $order));
                async function refresh() {
                    try {
                        const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
                        if (!response.ok) throw new Error('Tracking is temporarily unavailable.');
                        const data = await response.json();
                        if (data.status === 'delivered' || data.status === 'completed' || data.status === 'cancelled') {
                            if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
                            state.textContent = data.status === 'cancelled' ? 'Delivery cancelled.' : 'Delivery completed.';
                            updated.textContent = 'Live tracking has ended.';
                            stopped = true;
                            return;
                        }
                        const destinationValid = data.destination && validCoordinate(data.destination.latitude, data.destination.longitude);
                        if (destinationValid) {
                            destination = [Number(data.destination.latitude), Number(data.destination.longitude)];
                            if (!destinationMarker) destinationMarker = L.marker(destination).addTo(map).bindPopup('Delivery destination');
                            else destinationMarker.setLatLng(destination);
                            if (!driverMarker) map.setView(destination, 14);
                        }
                        const locationValid = data.location && validCoordinate(data.location.latitude, data.location.longitude);
                        if (!data.location) {
                            if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
                            state.textContent = data.driver_assigned ? 'Driver assigned, waiting for first GPS location.' : 'Waiting for driver assignment.';
                            updated.textContent = 'No driver coordinates have been received.';
                        } else if (!locationValid) {
                            if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
                            state.textContent = 'The latest GPS coordinates are invalid; waiting for a valid update.';
                            updated.textContent = 'Invalid coordinates were not shown on the map.';
                        } else if (!data.fresh) {
                            if (driverMarker) { driverMarker.setOpacity(0.35); driverMarker.setPopupContent('Last known driver location (stale)'); }
                            state.textContent = 'Last known driver location is stale. Waiting for a fresh GPS update.';
                            updated.textContent = data.location.recorded_at ? `Last update ${new Date(data.location.recorded_at).toLocaleTimeString()}` : 'Update time unavailable.';
                        } else {
                            const point = [Number(data.location.latitude), Number(data.location.longitude)];
                            if (!driverMarker) driverMarker = L.marker(point).addTo(map).bindPopup('Driver'); else driverMarker.setLatLng(point);
                            driverMarker.setOpacity(1);
                            driverMarker.setPopupContent('Driver location (live)');
                            state.textContent = 'Tracking active.';
                            updated.textContent = `Location updated ${new Date(data.location.recorded_at).toLocaleTimeString()}${data.location.accuracy_metres ? ` · accuracy ${Math.round(data.location.accuracy_metres)} m` : ''}`;
                            if (destination) map.fitBounds(L.latLngBounds([point, destination]).pad(0.2)); else map.setView(point, 15);
                        }
                        if (!destinationValid && !driverMarker) updated.textContent += ' Delivery destination coordinates are unavailable.';
                        if (!destinationValid) {
                            const destinationMessage = 'Delivery destination coordinates are unavailable. Contact support to correct the order location.';
                            state.textContent = locationValid && data.fresh
                                ? `Driver tracking is active. ${destinationMessage}`
                                : locationValid
                                    ? `Driver location is stale. ${destinationMessage}`
                                    : `Driver location is unavailable. ${destinationMessage}`;
                        }
                        if (destination && !locationValid) map.setView(destination, 14);
                    } catch (error) {
                        if (driverMarker) { driverMarker.setOpacity(0.35); driverMarker.setPopupContent('Last known driver location (stale)'); }
                        state.textContent = driverMarker ? 'Tracking network error. Last known location is marked stale.' : 'Tracking network error. No current driver location is available.';
                        updated.textContent = 'Unable to verify location freshness; waiting for the next successful poll.';
                    }
                }
                refresh();
                const timer = setInterval(() => { if (stopped) clearInterval(timer); else refresh(); }, 10000);
            })();
        </script>
    @endif

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-bold">Fuel ordered</h2>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($order->fuels as $fuel)
                <div class="flex flex-wrap justify-between gap-3 py-3">
                    <span>{{ $fuel->name }} · {{ $fuel->pivot->quantity_litres }} L</span>
                    <span class="font-semibold">UGX {{ number_format((float) $fuel->pivot->quantity_litres * (float) $fuel->pivot->unit_price, 2) }}</span>
                </div>
            @empty
                <p class="py-3 text-sm text-slate-500">No fuel line items are recorded for this order.</p>
            @endforelse
        </div>
    </section>

    @if ($order->status === 'out_for_delivery' && $order->payment?->status !== 'paid')
        <p class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Payment Pending — the driver must collect COD before you can confirm receipt.</p>
    @endif

    @if ($order->status === 'out_for_delivery' && $order->payment?->status === 'paid' && auth()->user()->can('confirmDelivery', $order))
        <form method="POST" action="{{ route('customer.orders.confirm-delivery', $order) }}" class="mt-6">
            @csrf
            <button class="rounded-lg bg-emerald-600 px-5 py-2.5 font-bold text-white hover:bg-emerald-700">Confirm receipt and delivery</button>
        </form>
    @endif
    @can('cancel', $order)
        <form method="POST" action="{{ route('customer.orders.cancel', $order) }}" class="mt-6">
            @csrf
            <button class="rounded-lg border border-red-300 px-5 py-2.5 font-bold text-red-700 hover:bg-red-50">Cancel order</button>
        </form>
    @endcan
@endsection
