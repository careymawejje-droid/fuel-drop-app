{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Add branch fuel')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.fuels.index', $provider) }}" class="text-sm text-slate-200 hover:text-white">Fuel inventory</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Page heading: the provider name stays visible under the title --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Add branch fuel</h1>
        <p class="mt-1 text-slate-600">{{ $provider->name }}</p>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Inventory form: same route and field names as before --}}
    <form method="POST" action="{{ route('provider.providers.fuels.store', $provider) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf

        {{-- Section 1: which branch and which fuel --}}
        <div class="border-b border-slate-200 p-6">
            <h2 class="font-bold">Branch and fuel</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">Branch
                    <select name="branch_id" required class="{{ $input }}">
                        <option value="">Select a branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Fuel
                    <select name="fuel_id" required class="{{ $input }}">
                        <option value="">Select a fuel</option>
                        @foreach ($fuels as $fuel)
                            <option value="{{ $fuel->id }}" @selected(old('fuel_id') == $fuel->id)>{{ $fuel->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        {{-- Section 2: price, stock and status --}}
        <div class="p-6">
            <h2 class="font-bold">Price and stock</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">Price per litre (UGX)
                    <input name="price_per_litre" type="number" step="0.01" min="0.01" value="{{ old('price_per_litre') }}" required class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Available stock (litres)
                    <input name="available_stock_litres" type="number" step="0.001" min="0" value="{{ old('available_stock_litres', 0) }}" required class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Status
                    <select name="status" required class="{{ $input }}">
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                </label>
            </div>
        </div>

        {{-- Actions: cancel returns to the fuel inventory --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('provider.providers.fuels.index', $provider) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save inventory</button>
        </div>
    </form>
@endsection