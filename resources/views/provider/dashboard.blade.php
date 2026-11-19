{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Provider dashboard')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.index') }}" class="text-sm text-slate-200 hover:text-white">Providers</a>
@endsection

@section('content')
    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Dashboard</h1>
    </section>

    {{-- Totals: one connected strip with dividers, same as the admin dashboard --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-label="Provider totals">
        <dl class="grid grid-cols-2 divide-x divide-y divide-slate-200 sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0">
            @foreach ([
                'Providers' => $stats['providers'],
                'Branches' => $stats['branches'],
                'Drivers' => $stats['drivers'],
                'Orders' => $stats['orders'],
                'Incoming orders' => $stats['incoming_orders'],
                'Active orders' => $stats['active_orders'],
            ] as $label => $value)
                <div class="px-5 py-4">
                    <dt class="text-sm text-slate-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Provider workspaces: one card per provider, with management links the user is allowed to use --}}
    <section class="mt-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold">Provider workspaces</h2>
            @can('view own notifications')
                <a href="{{ route('notifications.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">View notifications</a>
            @endcan
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            @forelse ($providers as $provider)
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <h3 class="p-5 font-bold">{{ $provider->name }}</h3>

                    {{-- Management links, each behind its own permission --}}
                    <nav class="flex flex-wrap gap-x-4 gap-y-2 border-t border-slate-200 bg-slate-50 px-5 py-3 text-sm" aria-label="{{ $provider->name }} management">
                        @can('manageBranches', $provider)
                            <a href="{{ route('provider.branches.index', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Branches</a>
                        @endcan
                        @can('manageInventory', $provider)
                            <a href="{{ route('provider.providers.fuels.index', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Fuel inventory</a>
                        @endcan
                        @can('manageDrivers', $provider)
                            <a href="{{ route('provider.providers.drivers.index', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Drivers</a>
                        @endcan
                        @can('manageTrucks', $provider)
                            <a href="{{ route('provider.trucks.index', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Trucks</a>
                        @endcan
                        @can('view', $provider)
                            <a href="{{ route('provider.providers.orders.index', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Orders</a>
                        @endcan
                    </nav>
                </article>
            @empty
                {{-- Empty state --}}
                <p class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500 md:col-span-2">No active provider workspaces are assigned to your account.</p>
            @endforelse
        </div>
    </section>

    {{-- Recent orders table --}}
    <section class="mt-8">
        <h2 class="mb-3 text-lg font-bold">Recent orders</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Order</th>
                            <th class="px-3 py-3 font-medium">Provider</th>
                            <th class="px-3 py-3 font-medium">Placed</th>
                            <th class="px-3 py-3 text-right font-medium">Total</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentOrders as $order)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4 font-semibold">{{ $order->order_number }}</td>
                                <td class="px-3 py-4">{{ $order->provider?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-4">{{ $order->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right tabular-nums">UGX {{ number_format((float) $order->total) }}</td>

                                {{-- Order status as a neutral badge --}}
                                <td class="px-3 py-4">
                                    <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" />
                                </td>

                                {{-- Row action --}}
                                <td class="px-5 py-4">
                                    @if ($order->provider)
                                        <a href="{{ route('provider.providers.orders.show', [$order->provider, $order]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Manage</a>
                                    @else
                                        <span class="text-slate-400">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            {{-- Empty state --}}
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">No orders are available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection