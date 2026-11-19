{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Order fuel')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('customer.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Dashboard</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Order fuel</h1>
        <p class="mt-1 text-slate-600">Choose a provider with available stock and review your Cash on Delivery total.</p>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Order form: same route and field names as before; the quote panel is part of the form so its button submits it --}}
    <form method="POST" action="{{ route('customer.orders.store') }}" class="grid items-start gap-6 lg:grid-cols-3">
        @csrf

        {{-- Left: order details, in sections --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white lg:col-span-2">

            {{-- Section 1: provider, fuel and quantity --}}
            <div class="border-b border-slate-200 p-6">
                <h2 class="font-bold">Fuel</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-medium sm:col-span-2">Fuel provider
                        <select id="provider_id" name="provider_id" required class="{{ $input }}">
                            <option value="">Select an available provider</option>
                            @foreach ($offerings->unique('provider_id')->sortBy('provider_name') as $offering)
                                <option value="{{ $offering['provider_id'] }}" @selected(old('provider_id') == $offering['provider_id'])>{{ $offering['provider_name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Fuel type
                        <select id="fuel_id" name="fuel_id" required class="{{ $input }}">
                            <option value="">Select fuel</option>
                            @foreach ($offerings->unique('fuel_id')->sortBy('fuel_name') as $offering)
                                <option value="{{ $offering['fuel_id'] }}" @selected(old('fuel_id') == $offering['fuel_id'])>{{ $offering['fuel_name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Quantity (litres)
                        <input id="quantity" name="quantity" type="number" min="3" max="10" step="0.001" value="{{ old('quantity', 3) }}" required class="{{ $input }}">
                    </label>
                </div>
            </div>

            {{-- Section 2: where to deliver --}}
            <div class="border-b border-slate-200 p-6">
                <h2 class="font-bold">Delivery location</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-950">
                        <p class="font-semibold">Uganda</p>
                        <p class="mt-1">Tell us the district and the area you know. Street names are optional. Confirm your exact delivery point on the map by pin or device location.</p>
                    </div>
                    <label class="block text-sm font-medium">District
                        <input name="district" value="{{ old('district') }}" required maxlength="60" autocomplete="address-level1" class="{{ $input }}">
                    </label>
                    <label class="block text-sm font-medium">City, town or municipality <span class="font-normal text-slate-500">(optional)</span>
                        <input name="locality" value="{{ old('locality') }}" maxlength="60" autocomplete="address-level2" class="{{ $input }}">
                    </label>
                    <label class="block text-sm font-medium">Area, neighbourhood or village <span class="font-normal text-slate-500">(optional)</span>
                        <input name="area" value="{{ old('area') }}" maxlength="70" class="{{ $input }}">
                    </label>
                    <label class="block text-sm font-medium">Road or street <span class="font-normal text-slate-500">(optional)</span>
                        <input name="road" value="{{ old('road') }}" maxlength="70" autocomplete="street-address" class="{{ $input }}">
                    </label>
                    <label class="block text-sm font-medium sm:col-span-2">Nearby landmark or additional directions <span class="font-normal text-slate-500">(optional)</span>
                        <input name="landmark" value="{{ old('landmark') }}" maxlength="90" placeholder="For example, near a shopping centre" class="{{ $input }}">
                    </label>

                    <div class="sm:col-span-2 rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><h3 class="font-semibold">Pin your delivery point</h3><p class="text-sm text-slate-600">Tap the map to place the pin, drag it to adjust, or use your device location. GPS permission is only requested if you choose that option.</p></div>
                            <button id="use-current-location" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">Use my current location</button>
                        </div>
                        <p id="location-message" class="mt-3 text-sm text-slate-600" role="status">No delivery pin selected yet. Place and confirm a pin to continue.</p>
                        <div id="order-location-map" class="z-0 mt-3 h-72 w-full rounded-lg sm:h-96" aria-label="Choose delivery point on map"></div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="block text-sm font-medium">Latitude<input id="delivery-latitude" name="latitude" type="number" min="-90" max="90" step="0.0000001" value="{{ old('latitude') }}" required readonly class="{{ $input }}"></label>
                            <label class="block text-sm font-medium">Longitude<input id="delivery-longitude" name="longitude" type="number" min="-180" max="180" step="0.0000001" value="{{ old('longitude') }}" required readonly class="{{ $input }}"></label>
                        </div>
                        <label class="mt-3 flex items-start gap-2 text-sm font-medium"><input id="confirm-delivery-location" name="location_confirmed" value="1" type="checkbox" required class="mt-1 rounded border-slate-300 text-amber-500 focus:ring-amber-400">I confirm this pin is the delivery point for my address.</label>
                    </div>

                    {{-- Vehicle: only shown when the customer has saved vehicles --}}
                    @if ($vehicles->isNotEmpty())
                        <label class="block text-sm font-medium sm:col-span-2">Vehicle <span class="font-normal text-slate-500">(optional)</span>
                            <select name="vehicle_id" class="{{ $input }}">
                                <option value="">No vehicle selected</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->name }}{{ $vehicle->plate ? ' · '.$vehicle->plate : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                </div>
            </div>

            {{-- Fixed terms: delivery time and payment method are not editable --}}
            <dl class="grid gap-4 bg-slate-50 px-6 py-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-semibold text-slate-900">Delivery time</dt>
                    <dd class="mt-1 text-slate-600">Immediate delivery only.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">Payment method</dt>
                    <dd class="mt-1 text-slate-600">Cash on Delivery</dd>
                </div>
            </dl>
        </div>

        {{-- Right: live quote. The element IDs below are updated by the script at the bottom of the page --}}
        <aside class="rounded-xl border border-slate-200 bg-white lg:sticky lg:top-6">
            <div class="p-6">
                <h2 class="text-lg font-bold">Quote</h2>
                <p id="quote_fuel" class="mt-3 text-sm text-slate-500">Select a provider and fuel.</p>
                <div class="mt-4 flex justify-between text-sm text-slate-600"><span>Fuel subtotal</span><span id="quote_subtotal" class="tabular-nums">UGX 0</span></div>
                <div class="flex justify-between py-2 text-sm text-slate-600"><span>Delivery fee</span><span class="tabular-nums">UGX {{ number_format($deliveryFee) }}</span></div>
                <div class="mt-2 flex justify-between border-t border-slate-200 pt-3 text-lg font-bold"><span>Total</span><span id="quote_total" class="tabular-nums">UGX {{ number_format($deliveryFee) }}</span></div>
            </div>
            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button class="w-full rounded-lg bg-amber-400 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Place order</button>
            </div>
        </aside>
    </form>

    {{-- Live quote script: unchanged. It reads the offerings and delivery fee from the server and updates the quote as the form changes --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const offerings = {{ Illuminate\Support\Js::from($offerings) }};
        const deliveryFee = {{ Illuminate\Support\Js::from($deliveryFee) }};
        const providerSelect = document.getElementById('provider_id');
        const fuelSelect = document.getElementById('fuel_id');
        const quantityInput = document.getElementById('quantity');
        const subtotalOutput = document.getElementById('quote_subtotal');
        const totalOutput = document.getElementById('quote_total');
        const fuelOutput = document.getElementById('quote_fuel');

        function updateQuote() {
            const offering = offerings.find((item) => String(item.provider_id) === providerSelect.value
                && String(item.fuel_id) === fuelSelect.value);
            const quantity = Number(quantityInput.value);
            const subtotal = offering && Number.isFinite(quantity) ? offering.price * quantity : 0;

            fuelOutput.textContent = offering
                ? `${offering.fuel_name} at UGX ${Number(offering.price).toLocaleString()} / L · ${offering.available_stock} L available`
                : 'Select an available provider and fuel.';
            subtotalOutput.textContent = `UGX ${Math.round(subtotal).toLocaleString()}`;
            totalOutput.textContent = `UGX ${Math.round(subtotal + deliveryFee).toLocaleString()}`;
        }

        providerSelect.addEventListener('change', updateQuote);
        fuelSelect.addEventListener('change', updateQuote);
        quantityInput.addEventListener('input', updateQuote);
        updateQuote();

        (() => {
            const map = L.map('order-location-map').setView([0.3476, 32.5825], 9);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
            const latitude = document.getElementById('delivery-latitude');
            const longitude = document.getElementById('delivery-longitude');
            const message = document.getElementById('location-message');
            const confirm = document.getElementById('confirm-delivery-location');
            const locationButton = document.getElementById('use-current-location');
            const ugandaBoundary = [[31.86617,-1.02736],[30.76986,-1.01455],[30.419105,-1.134659],[29.821519,-1.443322],[29.579466,-1.341313],[29.587838,-0.587406],[29.8195,-0.2053],[29.875779,0.59738],[30.086154,1.062313],[30.468508,1.583805],[30.85267,1.849396],[31.174149,2.204465],[30.77332,2.33989],[30.83385,3.50917],[31.24556,3.7819],[31.88145,3.55827],[32.68642,3.79232],[33.39,3.79],[34.005,4.249885],[34.47913,3.5556],[34.59607,3.05374],[35.03599,1.90584],[34.6721,1.17694],[34.18,0.515],[33.893569,0.109814],[33.903711,-0.95],[31.86617,-1.02736]];
            const isInUganda = (lat, lng) => {
                let inside = false;
                for (let i = 0, j = ugandaBoundary.length - 1; i < ugandaBoundary.length; j = i++) {
                    const [xi, yi] = ugandaBoundary[i], [xj, yj] = ugandaBoundary[j];
                    if ((yi > lat) !== (yj > lat) && lng < (xj - xi) * (lat - yi) / (yj - yi) + xi) inside = !inside;
                }
                return inside;
            };
            let marker = null;
            const setPin = (lat, lng, source, accuracy = null) => {
                if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return;
                if (!isInUganda(lat, lng)) {
                    confirm.checked = false;
                    const accuracyText = accuracy === null ? '' : ` Reported GPS accuracy: about ${Math.round(accuracy)} m.`;
                    message.textContent = `${source} location is outside Uganda.${accuracyText} Move the map pin to the delivery point in Uganda.`;
                    return;
                }
                latitude.value = lat.toFixed(7);
                longitude.value = lng.toFixed(7);
                if (!marker) {
                    marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                    marker.on('dragend', (event) => {
                        const point = event.target.getLatLng();
                        latitude.value = point.lat.toFixed(7);
                        longitude.value = point.lng.toFixed(7);
                        confirm.checked = false;
                        if (!isInUganda(point.lat, point.lng)) {
                            message.textContent = 'This pin is outside Uganda. Move it to the delivery point in Uganda.';
                            confirm.checked = false;
                            return;
                        }
                        message.textContent = 'Pin adjusted. Review the point and confirm it before placing your order.';
                    });
                } else marker.setLatLng([lat, lng]);
                marker.bindPopup('Confirmed delivery point').openPopup();
                map.setView([lat, lng], Math.max(map.getZoom(), 15));
                confirm.checked = false;
                const accuracyText = accuracy === null ? '' : ` GPS accuracy: about ${Math.round(accuracy)} m.`;
                message.textContent = `${source} pin selected.${accuracyText} Review it and tick the confirmation box before placing your order.`;
            };
            map.on('click', (event) => setPin(event.latlng.lat, event.latlng.lng, 'Map'));
            if (latitude.value && longitude.value) {
                const oldLat = Number(latitude.value), oldLng = Number(longitude.value);
                if (Number.isFinite(oldLat) && Number.isFinite(oldLng)) setPin(oldLat, oldLng, 'Previously selected');
            }
            locationButton.addEventListener('click', () => {
                if (!navigator.geolocation) { message.textContent = 'This browser does not support device location. You can place the pin on the map instead.'; return; }
                locationButton.disabled = true;
                message.textContent = 'Requesting device location…';
                navigator.geolocation.getCurrentPosition((position) => {
                    locationButton.disabled = false;
                    if (!Number.isFinite(position.coords.accuracy) || position.coords.accuracy <= 0 || position.coords.accuracy > 200) { message.textContent = `Device location accuracy is unavailable or low (${Number.isFinite(position.coords.accuracy) ? `${Math.round(position.coords.accuracy)} m` : 'unknown'}). Try again or place the pin manually.`; return; }
                    setPin(position.coords.latitude, position.coords.longitude, 'Device GPS', position.coords.accuracy);
                }, (error) => {
                    locationButton.disabled = false;
                    message.textContent = error.code === 1 ? 'Location permission was denied. You can place the pin on the map instead.' : error.code === 3 ? 'Device location timed out. Try again or place the pin manually.' : 'Device location is unavailable. You can place the pin on the map instead.';
                }, {enableHighAccuracy: true, maximumAge: 0, timeout: 20000});
            });
            setTimeout(() => map.invalidateSize(), 0);
        })();
    </script>
@endsection
