{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'All orders')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    @php
        // Permission checks that don't depend on the row, evaluated once instead of per order
        $user = auth()->user();
        $canSeeCustomer = $user->can('view all customers');
        $canSeeProvider = $user->can('manage providers');
        $canSeeDriverTruck = $user->can('view all drivers') && $user->can('manage providers');
        $canSeePayment = $user->can('view all payments');
    @endphp

    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">All orders</h1>
        <p class="mt-1 text-slate-600">Monitor order status and provider operations.</p>
    </section>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-5 flex flex-wrap gap-3">
        <label for="order-reference-search" class="sr-only">Search by order reference</label>
        <input id="order-reference-search" name="q" value="{{ $search }}" maxlength="64" placeholder="Search by order reference" class="min-w-64 rounded-lg border border-slate-300 px-3 py-2">
        <button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Search</button>
        @if ($search !== '')<a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a>@endif
    </form>

    {{-- Orders table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Order</th>
                        <th class="px-3 py-3 font-medium">Customer</th>
                        <th class="px-3 py-3 font-medium">Provider</th>
                        <th class="px-3 py-3 font-medium">Driver / truck</th>
                        <th class="px-3 py-3 text-right font-medium">Total</th>
                        <th class="px-3 py-3 font-medium">Payment</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="transition hover:bg-slate-50">
                            {{-- Order number --}}
                            <td class="px-5 py-4 font-semibold">{{ $order->order_number }}</td>

                            {{-- Customer, provider and driver/truck: a dash when the user may not see them --}}
                            <td class="px-3 py-4">@if ($canSeeCustomer){{ $order->customer->name }}@else—@endif</td>
                            <td class="px-3 py-4">@if ($canSeeProvider){{ $order->provider?->name ?? 'Unassigned' }}@else—@endif</td>
                            <td class="px-3 py-4">
                                @if ($canSeeDriverTruck)
                                    {{ $order->driver?->user?->name ?? '—' }} / {{ $order->truck?->plate ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Total, right-aligned so the digits line up --}}
                            <td class="whitespace-nowrap px-3 py-4 text-right tabular-nums">UGX {{ number_format((float) $order->total) }}</td>

                            {{-- Payment status as a neutral badge, hidden without permission --}}
                            <td class="px-3 py-4">
                                @if ($canSeePayment)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($order->payment?->status ?? 'unknown') }}</span>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Order status as a neutral badge --}}
                            <td class="px-3 py-4">
                                <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" />
                            </td>

                            {{-- Row action: depends on the order's provider, so it stays a per-row check --}}
                            <td class="px-5 py-4">
                                @if ($order->provider && $user->can('view', $order->provider))
                                    <a href="{{ route('provider.providers.orders.show', [$order->provider, $order]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Manage</a>
                                @elseif (! $order->provider)
                                    <span class="text-slate-400">No provider</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">No orders have been placed.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($orders->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
