{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Platform reports')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Operational reports</h1>
        <p class="mt-1 text-slate-600">Lifetime provider, order, customer, driver, and payment summaries.</p>
    </section>

    {{-- Totals: one connected strip with dividers, same as the dashboard --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-label="Platform totals">
        <dl class="grid grid-cols-2 divide-x divide-y divide-slate-200 lg:grid-cols-4 lg:divide-y-0">
            <div class="px-5 py-4">
                <dt class="text-sm text-slate-500">Providers</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $providers->count() }}</dd>
            </div>
            <div class="px-5 py-4">
                <dt class="text-sm text-slate-500">Customers</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $customersCount }}</dd>
            </div>
            <div class="px-5 py-4">
                <dt class="text-sm text-slate-500">Drivers</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $driversCount }}</dd>
            </div>
            <div class="px-5 py-4">
                <dt class="text-sm text-slate-500">Paid total</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">UGX {{ number_format((float) $paidTotal) }}</dd>
            </div>
        </dl>
    </section>

    {{-- Status breakdowns: orders and payments side by side --}}
    <section class="mt-6 grid gap-5 lg:grid-cols-2">

        {{-- Orders by status --}}
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Orders by status</h2>
            <div class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($orderStatuses as $status)
                    <div class="flex justify-between gap-4 py-3">
                        <span>{{ ucfirst(str_replace('_', ' ', $status->status)) }}</span>
                        <span class="font-semibold tabular-nums">{{ $status->total }}</span>
                    </div>
                @empty
                    <p class="py-3 text-slate-500">No orders are recorded.</p>
                @endforelse
            </div>
        </article>

        {{-- Payments by status: count and amount --}}
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Payments by status</h2>
            <div class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($paymentStatuses as $status)
                    <div class="flex justify-between gap-4 py-3">
                        <span>{{ ucfirst(str_replace('_', ' ', $status->status)) }}</span>
                        <span class="text-right font-semibold tabular-nums">{{ $status->total }} · UGX {{ number_format((float) $status->amount) }}</span>
                    </div>
                @empty
                    <p class="py-3 text-slate-500">No payments are recorded.</p>
                @endforelse
            </div>
        </article>
    </section>

    {{-- Provider operations table --}}
    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <h2 class="px-5 py-4 text-lg font-bold">Provider operations</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px] text-left text-sm">
                <thead class="border-t border-slate-200 bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Provider</th>
                        <th class="px-3 py-3 text-right font-medium">Branches</th>
                        <th class="px-3 py-3 text-right font-medium">Drivers</th>
                        <th class="px-3 py-3 text-right font-medium">Trucks</th>
                        <th class="px-5 py-3 text-right font-medium">Orders</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($providers as $provider)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold">{{ $provider->name }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ $provider->branches_count }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ $provider->drivers_count }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ $provider->trucks_count }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $provider->orders_count }}</td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No providers are configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection