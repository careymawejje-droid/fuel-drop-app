{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Provider orders')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.show', $provider) }}" class="text-sm text-slate-200 hover:text-white">{{ $provider->name }}</a>
@endsection

@section('content')
    {{-- Page heading: the provider name stays visible under the title --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Orders</h1>
        <p class="mt-1 text-slate-600">{{ $provider->name }}</p>
    </section>

    <form method="GET" action="{{ route('provider.providers.orders.index', $provider) }}" class="mb-5 flex flex-wrap gap-3">
        <label for="order-reference-search" class="sr-only">Search by order reference</label>
        <input id="order-reference-search" name="q" value="{{ $search }}" maxlength="64" placeholder="Search by order reference" class="min-w-64 rounded-lg border border-slate-300 px-3 py-2">
        <button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Search</button>
        @if ($search !== '')<a href="{{ route('provider.providers.orders.index', $provider) }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a>@endif
    </form>

    {{-- Success message after an action --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Orders table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Order</th>
                        <th class="px-3 py-3 font-medium">Customer</th>
                        <th class="px-3 py-3 font-medium">Placed</th>
                        <th class="px-3 py-3 font-medium">Driver / truck</th>
                        <th class="px-3 py-3 text-right font-medium">Total</th>
                        <th class="px-3 py-3 font-medium">Payment</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-semibold">{{ $order->order_number }}</td>
                            <td class="px-3 py-4">{{ $order->customer->name }}</td>
                            <td class="whitespace-nowrap px-3 py-4">{{ $order->placed_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-3 py-4">{{ $order->driver?->user?->name ?? '—' }} / {{ $order->truck?->plate ?? '—' }}</td>

                            {{-- Total, right-aligned so the digits line up --}}
                            <td class="whitespace-nowrap px-3 py-4 text-right tabular-nums">UGX {{ number_format((float) $order->total) }}</td>

                            {{-- Payment and order status as neutral badges --}}
                            <td class="px-3 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $order->payment?->status === 'paid' ? 'Collected' : 'Payment Pending' }}</span>
                                @if ($order->payment?->status === 'paid')
                                    <span class="mt-1 block text-xs text-slate-500">UGX {{ number_format((float) $order->payment->amount, 2) }} · {{ $order->payment->collected_at?->format('d M Y H:i') ?? 'Collected' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" />
                            </td>

                            {{-- Row action --}}
                            <td class="px-5 py-4">
                                <a href="{{ route('provider.providers.orders.show', [$provider, $order]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Manage</a>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">No orders are available.</td></tr>
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
