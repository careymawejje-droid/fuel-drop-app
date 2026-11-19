{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Manage order')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.orders.index', $provider) }}" class="text-sm text-slate-200 hover:text-white">Orders · {{ $provider->name }}</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Order summary: order number, address and status badge, then customer, delivery and payment in one divided row --}}
    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div>
                <h1 class="text-3xl font-bold">{{ $order->order_number }}</h1>
                <p class="mt-1 text-slate-600">{{ $order->delivery_address }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $provider->name }}</p>
            </div>
            <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800" />
        </div>

        <dl class="grid grid-cols-1 divide-y divide-slate-200 border-t border-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Customer</dt>
                <dd class="mt-1 font-bold">{{ $order->customer->name }}</dd>
                <dd class="text-sm text-slate-600">{{ $order->customer->phone }}</dd>
            </div>
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Delivery</dt>
                <dd class="mt-1 font-bold">{{ $order->driver?->user?->name ?? 'Driver not assigned' }}</dd>
                <dd class="text-sm text-slate-600">{{ $order->truck?->plate ?? 'Truck not assigned' }}</dd>
            </div>
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Payment</dt>
                <dd class="mt-1 font-bold tabular-nums">UGX {{ number_format((float) $order->total) }}</dd>
                <dd class="text-sm text-slate-600">{{ $order->payment?->status === 'paid' ? 'Collected' : 'Payment Pending' }}</dd>
                @if ($order->payment?->status === 'paid')
                    <dd class="text-sm text-slate-600">Collected UGX {{ number_format((float) $order->payment->amount, 2) }}</dd>
                    <dd class="text-sm text-slate-600">Collected at {{ $order->payment->collected_at?->format('d M Y H:i') ?? '—' }}</dd>
                @endif
            </div>
        </dl>
    </section>

    {{-- Success message after an action --}}
    @if (session('status'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Order items: quantities and unit prices from the order's saved pivot data --}}
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-bold">Order items</h2>
        <div class="mt-3 divide-y divide-slate-100 text-sm">
            @foreach ($order->fuels as $fuel)
                <div class="flex justify-between gap-3 py-3">
                    <span>{{ $fuel->name }} · {{ $fuel->pivot->quantity_litres }} L</span>
                    <span class="font-semibold tabular-nums">UGX {{ number_format((float) $fuel->pivot->quantity_litres * (float) $fuel->pivot->unit_price) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Rejection reason: only for rejected orders that have one --}}
    @if ($order->status === 'rejected' && $order->rejection_reason)
        <section class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-red-900">
            <h2 class="font-bold">Rejection reason</h2>
            <p class="mt-2 text-sm">{{ $order->rejection_reason }}</p>
        </section>
    @endif

    {{-- Stock reservations: shown when the order has any --}}
    @if ($order->stockReservations->isNotEmpty())
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Stock reservations</h2>
            <div class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($order->stockReservations as $reservation)
                    <div class="flex flex-wrap justify-between gap-3 py-3">
                        <span>{{ $reservation->branchFuel?->fuel?->name ?? 'Fuel' }} · {{ $reservation->branchFuel?->branch?->name ?? 'Branch unavailable' }}</span>
                        <span class="tabular-nums">{{ $reservation->quantity_litres }} L · {{ ucfirst($reservation->status) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Driver location: only while the order is being prepared or delivered --}}
    @if (in_array($order->status, ['assigned', 'fuel_loading', 'out_for_delivery'], true))
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Driver location</h2>
            @if ($latestLocation)
                <p class="mt-2 text-sm text-slate-600">
                    {{ $latestLocation->latitude }}, {{ $latestLocation->longitude }}
                    · Updated {{ $latestLocation->recorded_at }}
                </p>
            @elseif ($order->driver?->current_latitude !== null && $order->driver?->current_longitude !== null)
                <p class="mt-2 text-sm text-slate-600">{{ $order->driver->current_latitude }}, {{ $order->driver->current_longitude }}</p>
            @else
                <p class="mt-2 text-sm text-slate-500">No driver location has been reported yet.</p>
            @endif
        </section>
    @endif

    {{-- Actions for a pending order: accept it, or reject it with a reason --}}
    @if ($order->status === 'pending')
        @can('manageOrders', $provider)
            <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="p-6">
                    <h2 class="text-lg font-bold">Respond to this order</h2>

                    {{-- Accept --}}
                    <form method="POST" action="{{ route('provider.providers.orders.accept', [$provider, $order]) }}" class="mt-4">
                        @csrf
                        <button class="rounded-lg bg-emerald-600 px-5 py-2.5 font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">Accept order</button>
                    </form>
                </div>

                {{-- Reject: a reason is required --}}
                <form method="POST" action="{{ route('provider.providers.orders.reject', [$provider, $order]) }}" class="grid gap-3 border-t border-slate-200 bg-slate-50 p-6 sm:grid-cols-[1fr_auto]">
                    @csrf
                    <label class="text-sm font-medium">Rejection reason
                        <textarea name="rejection_reason" required maxlength="1000" rows="2" class="{{ $input }}">{{ old('rejection_reason') }}</textarea>
                    </label>
                    <div class="flex items-end">
                        <button class="rounded-lg border border-red-300 bg-white px-5 py-2.5 font-bold text-red-700 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2">Reject order</button>
                    </div>
                </form>
            </section>
        @endcan

    {{-- Assignment for an accepted or assigned order: pick a driver and truck --}}
    @elseif (in_array($order->status, ['accepted', 'assigned'], true))
        @if (auth()->user()->can('manageOrders', $provider) && auth()->user()->can('assignDeliveries', $provider))
            <form method="POST" action="{{ route('provider.providers.orders.assign', [$provider, $order]) }}" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                @csrf
                <div class="p-6">
                    <h2 class="text-lg font-bold">Delivery assignment</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <label class="block text-sm font-medium">Driver
                            <select name="driver_id" required class="{{ $input }}">
                                <option value="">Select a driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" @selected(old('driver_id', $order->driver_id) == $driver->id)>{{ $driver->user->name }}{{ $driver->id === $order->driver_id ? ' · Current' : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-medium">Truck
                            <select name="truck_id" required class="{{ $input }}">
                                <option value="">Select a truck</option>
                                @foreach ($trucks as $truck)
                                    <option value="{{ $truck->id }}" @selected(old('truck_id', $order->truck_id) == $truck->id)>{{ $truck->plate }} · {{ $truck->name }}{{ $truck->id === $order->truck_id ? ' · Current' : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">{{ $order->status === 'accepted' ? 'Assign delivery' : 'Update assignment' }}</button>
                </div>
            </form>
        @endif
    @endif
@endsection