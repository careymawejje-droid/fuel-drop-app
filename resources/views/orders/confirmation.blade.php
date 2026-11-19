{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Order placed')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('customer.orders.index') }}" class="text-sm text-slate-200 hover:text-white">My orders</a>
@endsection

@section('content')
    {{-- Confirmation card --}}
    <section class="mx-auto max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white">

        {{-- Success message: check icon, order number and who the order is waiting on --}}
        <div class="p-8 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-amber-400 text-slate-900"><i data-lucide="check" class="h-7 w-7"></i></div>
            <p class="mt-5 text-sm font-medium text-slate-500">Order placed</p>
            <h1 class="mt-1 text-3xl font-bold">{{ $order->order_number }}</h1>
            <p class="mt-3 text-slate-600">Your order is awaiting a response from {{ $order->provider?->name ?? 'the provider' }}.</p>
        </div>

        {{-- Order summary: fuel lines, delivery address and total --}}
        <div class="space-y-3 border-t border-slate-200 px-8 py-5 text-sm">
            @foreach ($order->fuels as $fuel)
                <div class="flex justify-between gap-4">
                    <span>{{ $fuel->name }} · {{ number_format($fuel->pivot->quantity_litres, 3) }} L</span>
                    <span class="tabular-nums">UGX {{ number_format($fuel->pivot->quantity_litres * $fuel->pivot->unit_price) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between gap-4">
                <span class="text-slate-500">Deliver to</span>
                <span class="text-right">{{ $order->delivery_address }}</span>
            </div>
            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 font-bold">
                <span>Total · Cash on Delivery</span>
                <span class="tabular-nums">UGX {{ number_format($order->total) }}</span>
            </div>
        </div>

        {{-- Actions: view the order, or go back to the dashboard --}}
        <div class="grid gap-3 border-t border-slate-200 bg-slate-50 px-8 py-5 sm:grid-cols-2">
            <a href="{{ route('customer.orders.show', $order) }}" class="rounded-lg bg-amber-400 px-5 py-2.5 text-center font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">View order</a>
            <a href="{{ route('customer.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center font-bold transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">Back to dashboard</a>
        </div>
    </section>
@endsection
