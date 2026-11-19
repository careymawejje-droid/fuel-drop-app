@extends('layouts.fueldrop')

@section('title', $provider->name)

@section('back-link')
    <a href="{{ route(auth()->user()->hasRole('admin') ? 'admin.providers.index' : 'provider.providers.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Provider workspace</a>
@endsection

@section('content')
    @php
        $btn = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400';
        $btnPrimary = 'inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2';
        $stats = [
            ['label' => 'Branches', 'icon' => 'building-2', 'value' => $provider->branches_count, 'can' => auth()->user()->can('manageBranches', $provider)],
            ['label' => 'Inventory', 'icon' => 'fuel', 'value' => $provider->branch_fuel_count, 'can' => auth()->user()->can('manageInventory', $provider)],
            ['label' => 'Drivers', 'icon' => 'users-round', 'value' => $provider->drivers_count, 'can' => auth()->user()->can('manageDrivers', $provider)],
            ['label' => 'Trucks', 'icon' => 'truck', 'value' => $provider->trucks_count, 'can' => auth()->user()->can('manageTrucks', $provider)],
        ];
        $stats = array_filter($stats, fn ($stat) => $stat['can']);
    @endphp

    <section class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div>
                <h1 class="text-3xl font-bold">{{ $provider->name }}</h1>
                <p class="mt-1 text-slate-600">{{ $provider->email ?: 'No provider email recorded' }}</p>
            </div>
            @role('admin')
                @if ($provider->admin_user_id)
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm text-slate-700">
                        <i data-lucide="user-check" class="h-4 w-4"></i>
                        Admin: {{ $provider->designatedAdmin?->name ?? 'Assigned' }}
                    </span>
                @else
                    @can('manage provider accounts')
                        <a href="{{ route('admin.providers.accounts.create', $provider) }}" class="{{ $btn }}">
                            <i data-lucide="user-plus" class="h-4 w-4"></i>Designate Provider
                        </a>
                    @endcan
                @endif
            @endrole
        </div>

        @if (count($stats))
            <dl class="grid grid-cols-2 divide-x divide-y divide-slate-200 border-t border-slate-200 sm:grid-cols-4 sm:divide-y-0">
                @foreach ($stats as $stat)
                    <div class="px-6 py-4">
                        <dt class="flex items-center gap-2 text-sm text-slate-500">
                            <i data-lucide="{{ $stat['icon'] }}" class="h-4 w-4 text-amber-600"></i>{{ $stat['label'] }}
                        </dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        <div class="flex flex-wrap gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            @can('manageDrivers', $provider)
                <a href="{{ route('provider.providers.drivers.index', $provider) }}" class="{{ $btn }}"><i data-lucide="users-round" class="h-4 w-4"></i>Manage drivers</a>
            @endcan
            @can('manageInventory', $provider)
                <a href="{{ route('provider.providers.fuels.index', $provider) }}" class="{{ $btn }}"><i data-lucide="fuel" class="h-4 w-4"></i>Manage inventory</a>
            @endcan
            @can('manageBranches', $provider)
                <a href="{{ route('provider.branches.index', $provider) }}" class="{{ $btn }}"><i data-lucide="git-branch" class="h-4 w-4"></i>Manage branches</a>
            @endcan
            @can('manageTrucks', $provider)
                <a href="{{ route('provider.trucks.index', $provider) }}" class="{{ $btn }}"><i data-lucide="truck" class="h-4 w-4"></i>Manage trucks</a>
            @endcan
            @can('view', $provider)
                <a href="{{ route('provider.providers.orders.index', $provider) }}" class="{{ $btn }}"><i data-lucide="clipboard-list" class="h-4 w-4"></i>Manage orders</a>
            @endcan
            @can('manageDrivers', $provider)
                <a href="{{ route('provider.providers.drivers.create', $provider) }}" class="{{ $btnPrimary }} sm:ml-auto">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>Add driver
                </a>
            @endcan
        </div>
    </section>

    <section class="mt-6 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
        <i data-lucide="shield-check" class="h-5 w-5 shrink-0"></i>
        <p>All branch, inventory, driver, truck, order, and payment actions remain limited to this provider on the server.</p>
    </section>
@endsection
