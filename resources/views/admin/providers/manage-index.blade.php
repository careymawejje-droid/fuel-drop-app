{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Platform providers')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    @php
        // Permission checks used by several rows, evaluated once instead of per provider
        $user = auth()->user();
        $canSeeAccount = $user->can('manage provider accounts') || ($user->can('manage users') && $user->can('manage platform users'));
        $canOpenAccountProfile = $user->can('manage users') && $user->can('manage platform users');
        $canManageAccounts = $user->can('manage provider accounts');
        $canSeeBranches = $user->can('view all branches');
        $canSeeDrivers = $user->can('view all drivers');
        $canSeeOrders = $user->can('view all orders');

        // Status badge colours
        $statusStyles = [
            'active' => 'bg-emerald-100 text-emerald-800',
            'suspended' => 'bg-amber-100 text-amber-800',
            'inactive' => 'bg-slate-200 text-slate-700',
        ];
    @endphp

    {{-- Page heading with the Add provider button --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Providers</h1>
            <p class="mt-1 max-w-2xl text-slate-600">Create provider businesses, manage their designated accounts, and control operational status.</p>
        </div>
        @can('manage providers')
            <a href="{{ route('admin.providers.create') }}" class="rounded-lg bg-amber-400 px-4 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Add provider</a>
        @endcan
    </div>

    {{-- Success message after an action --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Providers table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Provider</th>
                        <th class="px-3 py-3 font-medium">Designated account</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-3 py-3 text-right font-medium">Branches</th>
                        <th class="px-3 py-3 text-right font-medium">Drivers</th>
                        <th class="px-3 py-3 text-right font-medium">Orders</th>
                        <th class="px-5 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($providers as $provider)
                        <tr class="transition hover:bg-slate-50">
                            {{-- Name and email --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $provider->name }}</p>
                                <p class="text-xs text-slate-500">{{ $provider->email ?: 'No email recorded' }}</p>
                            </td>

                            {{-- Designated account name, hidden without permission --}}
                            <td class="px-3 py-4">
                                @if ($canSeeAccount)
                                    @if ($provider->designatedAdmin)
                                        {{ $provider->designatedAdmin->name }}
                                    @else
                                        <span class="text-slate-500">Not designated</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Status as a coloured badge --}}
                            <td class="px-3 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles[$provider->status] ?? 'bg-slate-200 text-slate-700' }}">{{ ucfirst($provider->status) }}</span>
                            </td>

                            {{-- Counts, right-aligned; a dash when the user may not see them --}}
                            <td class="px-3 py-4 text-right tabular-nums">@if ($canSeeBranches){{ $provider->branches_count }}@else—@endif</td>
                            <td class="px-3 py-4 text-right tabular-nums">@if ($canSeeDrivers){{ $provider->drivers_count }}@else—@endif</td>
                            <td class="px-3 py-4 text-right tabular-nums">@if ($canSeeOrders){{ $provider->orders_count }}@else—@endif</td>

                            {{-- Row actions: same permission rules as before --}}
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-x-4 gap-y-1">
                                    @can('manage providers')
                                        <a href="{{ route('admin.providers.edit', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Edit</a>
                                    @endcan
                                    @can('view', $provider)
                                        <a href="{{ route('provider.providers.show', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Workspace</a>
                                    @endcan
                                    @if ($provider->admin_user_id)
                                        @if ($canOpenAccountProfile)
                                            <a href="{{ route('admin.users.edit', $provider->admin_user_id) }}" class="font-semibold text-blue-700 hover:text-blue-900">Provider account</a>
                                        @endif
                                    @elseif ($canManageAccounts)
                                        <a href="{{ route('admin.providers.accounts.create', $provider) }}" class="font-semibold text-blue-700 hover:text-blue-900">Create account</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No providers have been created.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($providers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $providers->links() }}</div>
        @endif
    </div>
@endsection