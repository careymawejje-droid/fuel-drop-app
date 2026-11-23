<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDrop | Dashboard</title>
    <meta name="description" content="Order petrol, diesel or paraffin and follow the delivery driver live on the map.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    {{-- Reuse shared palette and spacing for the customer dashboard. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased" data-page="dash">

@php
    // ---------------------------------------------------------------
    // Permission checks, evaluated once here
    // ---------------------------------------------------------------
    $user = auth()->user();
    $canViewOrders = $user->can('view own orders');
    $canOrderFuel = $user->can('view available fuels') && $user->can('place orders');

    // The most recent order is the first one in the already-loaded list
    $latestOrder = $orders->first();
@endphp

@include('components.fueldrop.sidebar', ['role' => 'customer'])
<div class="min-h-screen lg:ml-64">
    {{-- Keep the existing desktop identity bar, applying only shared theme styling. --}}
    <header class="hidden border-b border-slate-200 bg-white px-6 py-4 lg:block">
        <p class="text-sm text-slate-500">Signed in as <span class="font-medium text-slate-900">{{ $user->name }}</span></p>
    </header>

        <main class="mx-auto max-w-5xl px-6 py-8">

            <!-- Page heading with the main actions -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-3xl font-bold">Dashboard</h1>
                <div class="flex flex-wrap gap-3">
                    @if ($canViewOrders)
                        <a href="{{ route('customer.orders.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 font-bold transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">Order history</a>
                    @endif
                    @if ($canOrderFuel)
                        <a href="{{ route('customer.orders.create') }}" class="rounded-lg bg-amber-400 px-4 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Order fuel</a>
                    @endif
                </div>
            </div>

            <!-- Summary: the most recent order, and order counts from the customer's own account -->
            <section class="mt-6 grid gap-5 md:grid-cols-3">

                <!-- Most recent order: read-only summary; live tracking is not implemented -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:col-span-2">
                    <p class="text-sm font-medium text-slate-500">Most recent order</p>
                    @if ($latestOrder)
                        <div class="mt-1 flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-bold">{{ $latestOrder->order_number }}</h2>
                            <x-order-status :status="$latestOrder->status" :payment-status="$latestOrder->payment?->status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" />
                        </div>
                        <p class="mt-1 text-slate-600">UGX {{ number_format($latestOrder->total) }}</p>
                        <p class="mt-1 text-sm text-slate-600">Payment: {{ $latestOrder->payment?->status === 'paid' ? 'Collected' : 'Payment Pending' }}</p>
                        @can('view', $latestOrder)
                            <a href="{{ route('customer.orders.show', $latestOrder) }}" class="mt-5 inline-block rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">View details</a>
                        @endcan
                    @else
                        <h2 class="mt-1 text-2xl font-bold">No orders yet</h2>
                        <p class="mt-1 text-slate-600">Your order history will appear here.</p>
                    @endif
                </div>

                <!-- Order counts -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <dl class="grid grid-cols-2 divide-x divide-slate-200">
                        <div class="px-5 py-4">
                            <dt class="text-sm text-slate-500">Total orders</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $ordersCount }}</dd>
                        </div>
                        <div class="px-5 py-4">
                            <dt class="text-sm text-slate-500">Pending</dt>
                            <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $pendingOrdersCount }}</dd>
                        </div>
                    </dl>
                    @if ($canViewOrders)
                        <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                            <a href="{{ route('customer.orders.index') }}" class="flex w-full justify-center rounded-lg bg-amber-400 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">View order history</a>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Recent orders: loaded through the authenticated customer's orders relationship -->
            <section class="mt-8">
                <h2 class="text-lg font-bold">Recent orders</h2>
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Order</th>
                                    <th class="px-3 py-3 font-medium">Fuel</th>
                                    <th class="px-3 py-3 font-medium">Date</th>
                                    <th class="px-3 py-3 text-right font-medium">Total</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($orders as $order)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-5 py-3.5 font-semibold">{{ $order->order_number }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-slate-600">
                                            @forelse ($order->fuels as $fuel)
                                                {{ $fuel->name }} · {{ $fuel->pivot->quantity_litres }} L{{ $loop->last ? '' : ', ' }}
                                            @empty
                                                No fuel details
                                            @endforelse
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-slate-600">{{ $order->created_at->format('d M Y') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-right tabular-nums">UGX {{ number_format($order->total) }}</td>
                                        <td class="whitespace-nowrap px-5 py-3.5">
                                            <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" />
                                        </td>
                                    </tr>
                                @empty
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-slate-500">You have not placed any orders yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

<!-- Render Lucide icons -->
<script>lucide.createIcons();</script>
</body>
</html>
