{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Order history')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-2 text-sm text-slate-200 hover:text-white">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Dashboard
    </a>
@endsection

@section('content')
    {{-- Page heading: orders are retrieved through the authenticated user's relationship --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Order history</h1>
        <p class="mt-1 text-slate-600">Review your orders and open their details.</p>
    </section>

    <form method="GET" action="{{ route('customer.orders.index') }}" class="mb-5 flex flex-wrap gap-3">
        <label for="order-reference-search" class="sr-only">Search by order reference</label>
        <input id="order-reference-search" name="q" value="{{ $search }}" maxlength="64" placeholder="Search by order reference" class="min-w-64 rounded-lg border border-slate-300 px-3 py-2">
        <button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Search</button>
        @if ($search !== '')<a href="{{ route('customer.orders.index') }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a>@endif
    </form>

    {{-- Orders table: the details link is authorized again by the order policy --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Order</th>
                        <th class="px-3 py-3 font-medium">Fuel</th>
                        <th class="px-3 py-3 font-medium">Placed</th>
                        <th class="px-3 py-3 text-right font-medium">Total</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="transition hover:bg-slate-50">
                            {{-- Order number --}}
                            <td class="px-5 py-4 font-semibold">{{ $order->order_number }}</td>

                            {{-- Fuels in the order: name and litres, comma-separated --}}
                            <td class="px-3 py-4 text-slate-600">
                                @forelse ($order->fuels as $fuel)
                                    {{ $fuel->name }} · {{ $fuel->pivot->quantity_litres }} L{{ $loop->last ? '' : ', ' }}
                                @empty
                                    No fuel details
                                @endforelse
                            </td>

                            <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $order->created_at->format('d M Y') }}</td>

                            {{-- Total, right-aligned so the digits line up --}}
                            <td class="whitespace-nowrap px-3 py-4 text-right tabular-nums">UGX {{ number_format($order->total) }}</td>

                            {{-- Order status as a neutral badge --}}
                            <td class="px-3 py-4">
                                <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" />
                            </td>

                            {{-- Row action --}}
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('customer.orders.show', $order) }}" class="font-semibold text-blue-700 hover:text-blue-900">Details</a>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i data-lucide="receipt-text" class="mx-auto h-8 w-8 text-slate-400"></i>
                                <h2 class="mt-3 font-bold">No orders yet</h2>
                                <p class="mt-1 text-sm text-slate-500">Your orders will appear here when ordering is available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($orders->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $orders->links() }}
            </div>
        @endif
    </section>
@endsection
