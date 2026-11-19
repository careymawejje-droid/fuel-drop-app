{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Edit truck')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.trucks.index', $provider) }}" class="text-sm text-slate-200 hover:text-white">Trucks · {{ $provider->name }}</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';

        // Status choices: "assigned" is only listed when the truck already has that status
        $statuses = array_merge(['available', 'maintenance', 'inactive'], $truck->status === 'assigned' ? ['assigned'] : []);
    @endphp

    {{-- Page heading: the provider name stays visible under the title --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Edit {{ $truck->plate }}</h1>
        <p class="mt-1 text-slate-600">{{ $provider->name }}</p>
    </section>

    {{-- Success message after saving --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Truck form: same route, method and field names as before --}}
    <form method="POST" action="{{ route('provider.trucks.update', [$provider, $truck]) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf
        @method('PUT')

        {{-- Section 1: name and plate --}}
        <div class="border-b border-slate-200 p-6">
            <h2 class="font-bold">Truck details</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">Truck name
                    <input name="name" value="{{ old('name', $truck->name) }}" required maxlength="255" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Plate number
                    <input name="plate" value="{{ old('plate', $truck->plate) }}" required maxlength="255" class="{{ $input }}">
                </label>
            </div>
        </div>

        {{-- Section 2: branch, capacity and status --}}
        <div class="p-6">
            <h2 class="font-bold">Assignment and capacity</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">Branch
                    <select name="branch_id" class="{{ $input }}">
                        <option value="">Any branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $truck->branch_id) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Capacity (litres)
                    <input name="capacity_litres" type="number" step="0.001" min="0.001" value="{{ old('capacity_litres', $truck->capacity_litres) }}" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Status
                    <select name="status" required class="{{ $input }}">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $truck->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        {{-- Actions: cancel returns to the trucks list --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('provider.trucks.index', $provider) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save truck</button>
        </div>
    </form>
@endsection