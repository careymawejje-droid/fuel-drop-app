{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'All branches')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    @php
        // Permission checks that don't depend on the row, evaluated once instead of per branch
        $user = auth()->user();
        $canSeeProvider = $user->can('manage providers');
        $canSeeInventory = $user->can('manage fuels');
        $canSeeDrivers = $user->can('view all drivers');
        $canSeeTrucks = $user->can('manage providers');

        // Status badge colours (same as the providers list)
        $statusStyles = [
            'active' => 'bg-emerald-100 text-emerald-800',
            'suspended' => 'bg-amber-100 text-amber-800',
            'inactive' => 'bg-slate-200 text-slate-700',
        ];
    @endphp

    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">All branches</h1>
        <p class="mt-1 text-slate-600">Review every branch across all providers.</p>
    </section>

    {{-- Branches table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Branch</th>
                        <th class="px-3 py-3 font-medium">Provider</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-3 py-3 text-right font-medium">Inventory</th>
                        <th class="px-3 py-3 text-right font-medium">Drivers</th>
                        <th class="px-3 py-3 text-right font-medium">Trucks</th>
                        <th class="px-5 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($branches as $branch)
                        <tr class="transition hover:bg-slate-50">
                            {{-- Branch name --}}
                            <td class="px-5 py-4 font-semibold">{{ $branch->name }}</td>

                            {{-- Provider name, hidden without permission --}}
                            <td class="px-3 py-4">@if ($canSeeProvider){{ $branch->provider->name }}@else—@endif</td>

                            {{-- Status as a coloured badge --}}
                            <td class="px-3 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles[$branch->status] ?? 'bg-slate-200 text-slate-700' }}">{{ ucfirst($branch->status) }}</span>
                            </td>

                            {{-- Counts, right-aligned; a dash when the user may not see them --}}
                            <td class="px-3 py-4 text-right tabular-nums">@if ($canSeeInventory){{ $branch->fuel_inventory_count }}@else—@endif</td>
                            <td class="px-3 py-4 text-right tabular-nums">@if ($canSeeDrivers){{ $branch->drivers_count }}@else—@endif</td>
                            <td class="px-3 py-4 text-right tabular-nums">@if ($canSeeTrucks){{ $branch->trucks_count }}@else—@endif</td>

                            {{-- Row action: depends on the branch, so it stays a per-row check --}}
                            <td class="px-5 py-4">
                                @can('manage', $branch)
                                    <a href="{{ route('provider.branches.edit', [$branch->provider, $branch]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Manage</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No branches have been created.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($branches->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $branches->links() }}</div>
        @endif
    </div>
@endsection