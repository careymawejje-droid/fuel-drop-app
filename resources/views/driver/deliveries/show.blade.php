@extends('layouts.fueldrop')

@section('title', 'Delivery '.$order->order_number)

@section('back-link')
    <a href="{{ route('driver.deliveries.index') }}" class="text-sm text-slate-600 hover:text-slate-900">My deliveries</a>
@endsection

@section('content')
    @php
        $payment = $order->payment;
        $canUpdateStatus = in_array($order->status, ['assigned', 'fuel_loading'], true)
            && auth()->user()->can('updateDeliveryStatus', $order);
        $canCollectCash = $order->status === 'out_for_delivery'
            && $payment?->payment_method === 'cash_on_delivery'
            && $payment->status === 'pending'
            && auth()->user()->can('collectPayment', $order);
    @endphp

    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div>
                <h1 class="text-3xl font-bold">{{ $order->order_number }}</h1>
                <p class="mt-1 text-slate-600">{{ $order->delivery_address }}</p>
            </div>
            <x-order-status :status="$order->status" :payment-status="$payment?->status" class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800" />
        </div>
        <dl class="grid grid-cols-1 divide-y divide-slate-200 border-t border-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Customer</dt>
                <dd class="mt-1 font-bold">{{ $order->customer->name }}</dd>
            </div>
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Truck</dt>
                <dd class="mt-1 font-bold">{{ $order->truck?->plate ?? 'Not assigned' }}</dd>
            </div>
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Amount due</dt>
                <dd class="mt-1 font-bold tabular-nums">UGX {{ number_format((float) $order->total, 2) }}</dd>
                <dd class="text-sm text-slate-600">Payment: {{ $payment?->status === 'paid' ? 'Collected' : 'Payment Pending' }}</dd>
                @if ($payment?->status === 'paid')
                    <dd class="text-sm text-slate-600">Collected: UGX {{ number_format((float) $payment->amount, 2) }}</dd>
                    <dd class="text-sm text-slate-600">Collected at: {{ $payment->collected_at?->format('d M Y H:i') ?? '—' }}</dd>
                @endif
            </div>
        </dl>
    </section>

    @if (session('status'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-bold">Delivery point</h2>
        <p class="mt-1 text-sm text-slate-600">{{ $order->delivery_address }}</p>
        <p class="mt-2 flex flex-wrap gap-4 text-xs text-slate-600"><span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>Customer destination</span><span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-blue-600"></span>Your current GPS position</span></p>
        <div id="driver-delivery-map" class="z-0 mt-3 h-72 w-full rounded-xl sm:h-96" aria-label="Customer delivery destination and driver location"></div>
        @if ($order->delivery_latitude === null || $order->delivery_longitude === null)
            <p class="mt-2 text-sm text-amber-800">This order has no confirmed map coordinates. Use the address and landmark instructions above.</p>
        @endif
    </section>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const map = L.map('driver-delivery-map').setView([0.3476, 32.5825], 9);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
            const lat = @json($order->delivery_latitude);
            const lng = @json($order->delivery_longitude);
            if (lat !== null && lng !== null && Number.isFinite(Number(lat)) && Number.isFinite(Number(lng))) {
                L.marker([Number(lat), Number(lng)]).addTo(map).bindPopup('Customer destination').openPopup();
                map.setView([Number(lat), Number(lng)], 15);
            }
            window.fuelDropSetCurrentDriverPosition = (latitude, longitude) => {
                const point = [latitude, longitude];
                // Read the delivery marker color from the single shared palette.
                const driverColor = getComputedStyle(document.documentElement).getPropertyValue('--fd-forest').trim();
                if (!window.fuelDropDriverMarker) window.fuelDropDriverMarker = L.circleMarker(point, {radius: 8, color: driverColor, fillColor: driverColor, fillOpacity: 0.9}).addTo(map).bindPopup('Your current GPS position');
                else window.fuelDropDriverMarker.setLatLng(point);
                if (lat === null || lng === null) map.setView(point, 15);
            };
            setTimeout(() => map.invalidateSize(), 0);
        })();
    </script>

    @if (in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true))
        <section class="mt-6 rounded-xl border border-blue-200 bg-white p-6">
            <h2 class="text-lg font-bold">Delivery location sharing</h2>
            <p id="gps-state" class="mt-2 text-sm text-slate-600" role="status">Location sharing is not active.</p>
            <p id="gps-updated" class="mt-1 text-xs text-slate-500"></p>
            <button id="gps-start" type="button" class="mt-3 rounded-lg bg-blue-700 px-4 py-2 font-bold text-white">Start location sharing</button>
        </section>
        <script>
            (() => {
                const state = document.getElementById('gps-state');
                const updated = document.getElementById('gps-updated');
                const start = document.getElementById('gps-start');
                let watch = null;
                let lastSent = 0;
                let stoppedForVisibility = false;
                const stop = () => { if (watch !== null) navigator.geolocation.clearWatch(watch); watch = null; };
                window.addEventListener('pagehide', stop);
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden && watch !== null) {
                        stop();
                        stoppedForVisibility = true;
                        state.textContent = 'Page hidden. GPS sharing paused; it is not active.';
                        start.textContent = 'Resume location sharing';
                    } else if (!document.hidden && stoppedForVisibility) {
                        stoppedForVisibility = false;
                        state.textContent = 'Page visible again. GPS sharing is stopped; tap Resume to restart it.';
                        start.textContent = 'Resume location sharing';
                    }
                });
                start.addEventListener('click', () => {
                    if (!navigator.geolocation) { state.textContent = 'This browser does not provide GPS location.'; return; }
                    if (watch !== null) { stop(); state.textContent = 'Location sharing paused.'; start.textContent = 'Resume location sharing'; return; }
                    state.textContent = 'Waiting for GPS permission and a location fix…';
                    watch = navigator.geolocation.watchPosition(async (position) => {
                        if (watch === null || document.hidden) return;
                        if (Date.now() - lastSent < 5000) return;
                        lastSent = Date.now();
                        if (position.coords.accuracy > 200) { state.textContent = `GPS accuracy is low (${Math.round(position.coords.accuracy)} m); waiting for a better fix.`; return; }
                        window.fuelDropSetCurrentDriverPosition(position.coords.latitude, position.coords.longitude);
                        try {
                            const response = await fetch(@json(route('driver.location.update')), {
                                method: 'POST', credentials: 'same-origin', headers: {
                                    'Content-Type': 'application/json', 'Accept': 'application/json',
                                    'X-CSRF-TOKEN': @json(csrf_token()),
                                }, body: JSON.stringify({order_id: @json($order->id), latitude: position.coords.latitude,
                                    longitude: position.coords.longitude, accuracy_metres: position.coords.accuracy})
                            });
                            if (!response.ok) {
                                if (response.status === 403) { stop(); state.textContent = 'This delivery is no longer trackable. Location sharing stopped.'; start.textContent = 'Location sharing stopped'; start.disabled = true; return; }
                                throw new Error('Network update failed.');
                            }
                            if (watch !== null && !document.hidden) {
                                state.textContent = 'Location sharing is active.';
                                updated.textContent = `Last sent ${new Date().toLocaleTimeString()}`;
                            }
                        } catch (error) {
                            state.textContent = watch !== null && !document.hidden ? `${error.message} Retrying when a new location is available.` : 'Location sharing is stopped; the update did not complete.';
                        }
                    }, (error) => {
                        stop();
                        state.textContent = error.code === 1 ? 'Location permission was denied. GPS sharing is stopped.' : 'GPS is unavailable. Sharing stopped; check device location settings.';
                        start.textContent = 'Restart location sharing';
                    }, {enableHighAccuracy: true, maximumAge: 5000, timeout: 20000});
                    start.textContent = 'Pause location sharing';
                });
            })();
        </script>
    @endif

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-bold">Fuel to deliver</h2>
        <div class="mt-3 divide-y divide-slate-100 text-sm">
            @forelse ($order->fuels as $fuel)
                <div class="flex flex-wrap justify-between gap-3 py-3">
                    <span>{{ $fuel->name }} · {{ $fuel->pivot->quantity_litres }} L</span>
                    <span class="font-semibold tabular-nums">UGX {{ number_format((float) $fuel->pivot->quantity_litres * (float) $fuel->pivot->unit_price, 2) }}</span>
                </div>
            @empty
                <p class="py-3 text-slate-500">No fuel line items are recorded for this order.</p>
            @endforelse
        </div>
    </section>

    @if ($canUpdateStatus)
        <form method="POST" action="{{ route('driver.deliveries.status', $order) }}" class="mt-6">
            @csrf
            <input type="hidden" name="status" value="{{ $order->status === 'assigned' ? 'fuel_loading' : 'out_for_delivery' }}">
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 hover:bg-amber-300">
                {{ $order->status === 'assigned' ? 'Start fuel loading' : 'Start delivery' }}
            </button>
        </form>
    @endif

    @if ($order->status === 'out_for_delivery' && $payment?->status !== 'paid')
        <section class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-5">
            <h2 class="text-lg font-bold text-amber-950">Payment Pending</h2>
            <p class="mt-1 text-sm text-amber-900">Collect UGX {{ number_format((float) $order->total, 2) }} from the customer before requesting delivery confirmation.</p>
            @if ($canCollectCash)
                <form method="POST" action="{{ route('driver.deliveries.cash-collection', $order) }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <label class="text-sm font-medium text-slate-800">
                        Amount collected (UGX)
                        <input name="amount" type="number" min="0.01" step="0.01" max="99999999.99" required value="{{ old('amount', number_format((float) $order->total, 2, '.', '')) }}" class="mt-1 block w-56 rounded-lg border border-slate-300 bg-white px-3 py-2">
                    </label>
                    <button class="rounded-lg bg-emerald-600 px-5 py-2.5 font-bold text-white hover:bg-emerald-700">Record cash collection</button>
                </form>
            @endif
        </section>
    @elseif ($order->status === 'out_for_delivery' && $payment?->status === 'paid')
        <p class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-900">Payment collected. Awaiting customer confirmation.</p>
    @endif
@endsection
