{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Edit driver')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('provider.providers.drivers.index', $provider) }}" class="text-sm text-slate-200 hover:text-white">Drivers · {{ $provider->name }}</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Edit {{ $driver->user->name }}</h1>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Driver form: same route, method and field names as before --}}
    <form method="POST" action="{{ route('provider.providers.drivers.update', [$provider, $driver]) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf
        @method('PUT')

        {{-- Fields --}}
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <label class="block text-sm font-medium sm:col-span-2">Full name
                <input name="name" value="{{ old('name', $driver->user->name) }}" required maxlength="255" autocomplete="name" class="{{ $input }}">
            </label>
            <label class="block text-sm font-medium">Phone
                <input name="phone" type="tel" value="{{ old('phone', $driver->user->phone) }}" maxlength="20" autocomplete="tel" class="{{ $input }}">
            </label>
            <label class="block text-sm font-medium">Branch
                <select name="branch_id" class="{{ $input }}">
                    <option value="">No branch assignment</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $driver->branch_id) == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">Truck plate
                <input name="truck_plate" value="{{ old('truck_plate', $driver->truck_plate) }}" maxlength="255" class="{{ $input }}">
            </label>

            <label class="block text-sm font-medium">Availability
                <select name="available_status" required class="{{ $input }}">
                    @foreach (['available' => 'Available', 'inactive' => 'Inactive'] + ($driver->available_status === 'assigned' ? ['assigned' => 'Assigned'] : []) as $status => $label)
                        <option value="{{ $status }}" @selected(old('available_status', $driver->available_status) === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        {{-- Actions: cancel returns to the drivers list --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('provider.providers.drivers.index', $provider) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save changes</button>
        </div>
    </form>
@endsection