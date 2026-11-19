{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Add provider')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.providers.index') }}" class="text-sm text-slate-200 hover:text-white">Providers</a>
@endsection

@section('content')
    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Add provider</h1>
        <p class="mt-1 text-slate-600">Enter the provider's details. You can designate its account after it is created.</p>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p>{{ $errors->first() }}</p>
        </div>
    @endif

    {{-- Form: same route and field names as before --}}
    <form method="POST" action="{{ route('admin.providers.store') }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf

        {{-- Fields --}}
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <label class="block text-sm font-medium">Provider name
                <input name="name" value="{{ old('name') }}" required maxlength="255"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            </label>
            <label class="block text-sm font-medium">Phone
                <input name="phone" value="{{ old('phone') }}" maxlength="20" autocomplete="tel"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            </label>
            <label class="block text-sm font-medium sm:col-span-2">Email
                <input name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            </label>
        </div>

        {{-- Actions: cancel returns to the providers list --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('admin.providers.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Create provider</button>
        </div>
    </form>
@endsection