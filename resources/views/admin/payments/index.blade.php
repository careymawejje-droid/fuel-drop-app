{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'All payments')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Payments</h1>
        <p class="mt-1 text-slate-600">Review payment status and cash collections.</p>
    </section>

    {{-- Payments table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Order</th>
                        <th class="px-3 py-3 font-medium">Customer</th>
                        <th class="px-3 py-3 font-medium">Provider</th>
                        <th class="px-3 py-3 font-medium">Method</th>
                        <th class="px-3 py-3 text-right font-medium">Amount</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-3 py-3 font-medium">Collected by</th>
                        <th class="px-5 py-3 font-medium">Collected at</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-semibold">{{ $payment->order->order_number }}</td>
                            <td class="px-3 py-4">{{ $payment->order->customer->name }}</td>
                            <td class="px-3 py-4">{{ $payment->order->provider?->name ?? 'Unassigned' }}</td>
                            <td class="px-3 py-4">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>

                            {{-- Amount, right-aligned so the digits line up --}}
                            <td class="whitespace-nowrap px-3 py-4 text-right tabular-nums">UGX {{ number_format((float) $payment->amount) }}</td>

                            {{-- Payment status as a neutral badge --}}
                            <td class="px-3 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($payment->status) }}</span>
                            </td>

                            {{-- Cash collection details; a dash when not collected --}}
                            <td class="px-3 py-4">{{ $payment->collectedByDriver?->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $payment->collected_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">No payments have been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($payments->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection