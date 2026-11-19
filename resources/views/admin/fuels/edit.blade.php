{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Edit branch fuel')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.fuels.index', $provider) }}" class="text-sm text-slate-200 hover:text-white">Fuel inventory</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Page heading: provider and branch stay visible under the title --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Edit {{ $branchFuel->fuel->name }}</h1>
        <p class="mt-1 text-slate-600">{{ $provider->name }} · {{ $branchFuel->branch->name }}</p>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Inventory form: same route, method and field names as before --}}
    <form method="POST" action="{{ route('provider.providers.fuels.update', [$provider, $branchFuel]) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf
        @method('PUT')

        {{-- Fields --}}
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <label class="block text-sm font-medium">Price per litre (UGX)
                <input name="price_per_litre" type="number" step="0.01" min="0.01" value="{{ old('price_per_litre', $branchFuel->price_per_litre) }}" required class="{{ $input }}">
            </label>
            <label class="block text-sm font-medium">Available stock (litres)
                <input name="available_stock_litres" type="number" step="0.001" min="0" value="{{ old('available_stock_litres', $branchFuel->available_stock_litres) }}" required class="{{ $input }}">
            </label>
            <label class="block text-sm font-medium">Status
                <select name="status" required class="{{ $input }}">
                    <option value="active" @selected(old('status', $branchFuel->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $branchFuel->status) === 'inactive')>Inactive</option>
                </select>
            </label>
        </div>

        {{-- Actions: cancel returns to the fuel inventory --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('provider.providers.fuels.index', $provider) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save changes</button>
        </div>
    </form>
@endsection