{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Platform settings')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Platform settings</h1>
        <p class="mt-1 text-slate-600">These settings control the public contact recipient and customer delivery totals.</p>
    </section>

    {{-- Success message after saving --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Settings form: same route, method and field names as before --}}
    <form method="POST" action="{{ route('admin.settings.update') }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf
        @method('PUT')

        {{-- Section 1: support contact --}}
        <div class="border-b border-slate-200 p-6">
            <h2 class="font-bold">Support contact</h2>
            <label class="mt-4 block text-sm font-medium">Support contact email
                <input name="contact_email" type="email" value="{{ old('contact_email', $settings->contact_email) }}" required maxlength="255" autocomplete="email" class="{{ $input }}">
            </label>
        </div>

        {{-- Section 2: customer delivery fee --}}
        <div class="p-6">
            <h2 class="font-bold">Delivery</h2>
            <label class="mt-4 block text-sm font-medium">Delivery fee (UGX)
                <input name="delivery_fee" type="number" step="0.01" min="0" value="{{ old('delivery_fee', $settings->delivery_fee) }}" required class="{{ $input }}">
            </label>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save settings</button>
        </div>
    </form>
@endsection