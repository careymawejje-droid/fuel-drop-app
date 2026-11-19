{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Assigned deliveries')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('driver.dashboard') }}" class="flex items-center gap-2 text-sm text-slate-200 hover:text-white">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Dashboard
    </a>
@endsection

@section('content')
    {{-- Page heading: this list contains only orders assigned to the signed-in driver --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Assigned deliveries</h1>
        <p class="mt-1 text-slate-600">Open an assigned delivery to review its details.</p>
    </section>

    <form method="GET" action="{{ route('driver.deliveries.index') }}" class="mb-5 flex flex-wrap gap-3">
        <label for="order-reference-search" class="sr-only">Search by order reference</label>
        <input id="order-reference-search" name="q" value="{{ $search }}" maxlength="64" placeholder="Search by order reference" class="min-w-64 rounded-lg border border-slate-300 px-3 py-2">
        <button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Search</button>
        @if ($search !== '')<a href="{{ route('driver.deliveries.index') }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a>@endif
    </form>

    {{-- Delivery cards: state-changing delivery actions are intentionally not offered here --}}
    <section class="grid gap-5 md:grid-cols-2">
        @forelse ($orders as $order)
            <article class="flex flex-col rounded-xl border border-slate-200 bg-white">

                {{-- Order number, status badge and delivery address --}}
                <div class="p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h2 class="text-xl font-bold">{{ $order->order_number }}</h2>
                        <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800" />
                    </div>
                    <p class="mt-2 text-slate-600">{{ $order->delivery_address }}</p>
                </div>

                {{-- Customer and truck, in a divided row --}}
                <dl class="grid grid-cols-2 divide-x divide-slate-200 border-t border-slate-200 text-sm">
                    <div class="px-6 py-4">
                        <dt class="text-slate-500">Customer</dt>
                        <dd class="mt-1 font-semibold">{{ $order->customer?->name ?? 'Customer unavailable' }}</dd>
                    </div>
                    <div class="px-6 py-4">
                        <dt class="text-slate-500">Truck</dt>
                        <dd class="mt-1 font-semibold">{{ $order->truck?->plate ?? 'Not assigned' }}</dd>
                    </div>
                </dl>

                {{-- Action: open the delivery details --}}
                <div class="mt-auto border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <a href="{{ route('driver.deliveries.show', $order) }}" class="inline-flex w-full justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 font-bold transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">View delivery</a>
                </div>
            </article>
        @empty
            {{-- Empty state --}}
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center md:col-span-2">
                <i data-lucide="package-check" class="mx-auto h-8 w-8 text-slate-400"></i>
                <h2 class="mt-3 text-lg font-bold">No assigned deliveries</h2>
                <p class="mt-1 text-sm text-slate-500">Assigned orders will appear here.</p>
            </div>
        @endforelse
    </section>

    {{-- Pagination --}}
    @if ($orders->hasPages())
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
