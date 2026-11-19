{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Fuel inventory')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.show', $provider) }}" class="text-sm text-slate-200 hover:text-white">{{ $provider->name }}</a>
@endsection

@section('content')
    @php
        // Status badge colours (same as the other lists)
        $statusStyles = [
            'active' => 'bg-emerald-100 text-emerald-800',
            'inactive' => 'bg-slate-200 text-slate-700',
        ];
    @endphp

    {{-- Page heading with the Add branch fuel button --}}
    <section class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Fuel inventory</h1>
            <p class="mt-1 text-slate-600">{{ $provider->name }}</p>
        </div>
        <a href="{{ route('provider.providers.fuels.create', $provider) }}" class="rounded-lg bg-amber-400 px-4 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Add branch fuel</a>
    </section>

    {{-- Success message after an action --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Inventory table --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Branch</th>
                        <th class="px-3 py-3 font-medium">Fuel</th>
                        <th class="px-3 py-3 text-right font-medium">Price / L</th>
                        <th class="px-3 py-3 text-right font-medium">Available</th>
                        <th class="px-3 py-3 text-right font-medium">Reserved</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($branchFuels as $branchFuel)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-semibold">{{ $branchFuel->branch->name }}</td>
                            <td class="px-3 py-4">{{ $branchFuel->fuel->name }}</td>

                            {{-- Price and stock figures, right-aligned so the digits line up --}}
                            <td class="px-3 py-4 text-right tabular-nums">UGX {{ number_format($branchFuel->price_per_litre) }}</td>
                            <td class="px-3 py-4 text-right tabular-nums">{{ number_format($branchFuel->available_stock_litres, 3) }} L</td>
                            <td class="px-3 py-4 text-right tabular-nums">{{ number_format($branchFuel->reserved_stock_litres, 3) }} L</td>

                            {{-- Status as a coloured badge --}}
                            <td class="px-3 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles[$branchFuel->status] ?? 'bg-slate-200 text-slate-700' }}">{{ ucfirst($branchFuel->status) }}</span>
                            </td>

                            {{-- Row action --}}
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('provider.providers.fuels.edit', [$provider, $branchFuel]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Edit</a>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No inventory has been configured for this provider.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($branchFuels->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $branchFuels->links() }}</div>
        @endif
    </section>
@endsection