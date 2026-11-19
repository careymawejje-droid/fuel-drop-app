{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Payment details')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('customer.orders.show', $order) }}" class="text-sm text-slate-200 hover:text-white">Order {{ $order->order_number }}</a>
@endsection

@section('content')
    {{-- Payment card --}}
    <section class="mx-auto max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white">

        {{-- Heading: payment method and what it means --}}
        <div class="p-6">
            <p class="text-sm font-medium text-slate-500">Payment</p>
            <h1 class="mt-1 text-3xl font-bold">Cash on Delivery</h1>
            <p class="mt-2 text-slate-600">Payment for order {{ $order->order_number }} is collected by the driver at delivery.</p>
        </div>

        {{-- Amount due and payment status, in a divided row --}}
        <dl class="grid grid-cols-1 divide-y divide-slate-200 border-t border-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Amount due</dt>
                <dd class="mt-1 text-xl font-bold tabular-nums">UGX {{ number_format($order->payment?->amount ?? $order->total) }}</dd>
            </div>
            <div class="px-6 py-4">
                <dt class="text-sm text-slate-500">Payment status</dt>
                <dd class="mt-1">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $order->payment?->status === 'paid' ? 'Collected' : 'Payment Pending' }}</span>
                </dd>
            </div>
        </dl>

        @if ($order->payment?->status === 'paid')
            <dl class="grid grid-cols-1 divide-y divide-slate-200 border-t border-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <div class="px-6 py-4">
                    <dt class="text-sm text-slate-500">Amount collected</dt>
                    <dd class="mt-1 font-bold tabular-nums">UGX {{ number_format((float) $order->payment->amount, 2) }}</dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-sm text-slate-500">Collected at</dt>
                    <dd class="mt-1 font-bold">{{ $order->payment->collected_at?->format('d M Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        @endif

        {{-- Action: back to the order --}}
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('customer.orders.show', $order) }}" class="inline-flex rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Back to order</a>
        </div>
    </section>
@endsection