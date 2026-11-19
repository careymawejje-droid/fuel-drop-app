{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Provider drivers')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.show', $provider) }}" class="flex items-center gap-2 text-sm text-slate-200 hover:text-white">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        {{ $provider->name }}
    </a>
@endsection

@section('content')
    {{-- Page heading with the Add driver button: the provider and driver collection are already authorized and scoped --}}
    <section class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Drivers</h1>
            <p class="mt-1 text-slate-600">{{ $provider->name }}</p>
        </div>
        <a href="{{ route('provider.providers.drivers.create', $provider) }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
            <i data-lucide="user-plus" class="h-4 w-4"></i>
            Add driver
        </a>
    </section>

    {{-- Drivers table: records stay scoped to the authorized provider --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-3 py-3 font-medium">Phone</th>
                        <th class="px-3 py-3 font-medium">Branch</th>
                        <th class="px-3 py-3 font-medium">Truck plate</th>
                        <th class="px-3 py-3 font-medium">Availability</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($drivers as $driver)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-semibold">{{ $driver->user->name }}</td>
                            <td class="px-3 py-4 text-slate-600">{{ $driver->user->phone ?: 'Not provided' }}</td>
                            <td class="px-3 py-4 text-slate-600">{{ $driver->branch?->name ?? 'Not assigned' }}</td>
                            <td class="px-3 py-4 text-slate-600">{{ $driver->truck_plate ?: 'Not assigned' }}</td>

                            {{-- Availability as a neutral badge --}}
                            <td class="px-3 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($driver->available_status) }}</span>
                            </td>

                            {{-- Row action --}}
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('provider.providers.drivers.edit', [$provider, $driver]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Edit</a>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i data-lucide="users-round" class="mx-auto h-8 w-8 text-slate-400"></i>
                                <h2 class="mt-3 font-bold">No drivers yet</h2>
                                <p class="mt-1 text-sm text-slate-500">Add a driver to begin managing this provider's team.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($drivers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $drivers->links() }}
            </div>
        @endif
    </section>
@endsection