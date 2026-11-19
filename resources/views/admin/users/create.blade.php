{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Add customer')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-200 hover:text-white">Users</a>
@endsection

@section('content')
    @php
        // Shared input style so every field looks the same
        $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200';
    @endphp

    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Add customer</h1>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Customer form: same route and field names as before --}}
    <form method="POST" action="{{ route('admin.users.store') }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf

        {{-- Section 1: who the customer is --}}
        <div class="border-b border-slate-200 p-6">
            <h2 class="font-bold">Customer details</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium sm:col-span-2">Full name
                    <input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Email
                    <input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Phone
                    <input name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" class="{{ $input }}">
                </label>

                {{-- Account role: fixed to Customer on this page, shown as a read-only badge --}}
                <div class="text-sm">
                    <span class="block font-medium">Account role</span>
                    <p class="mt-1 inline-flex rounded-full bg-slate-100 px-3 py-2 font-semibold text-slate-700">Customer</p>
                </div>
            </div>
        </div>

        {{-- Section 2: sign-in password --}}
        <div class="p-6">
            <h2 class="font-bold">Sign-in password</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">Password
                    <input name="password" type="password" required minlength="8" autocomplete="new-password" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Confirm password
                    <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="{{ $input }}">
                </label>
            </div>
        </div>

        {{-- Actions: cancel returns to the users list --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('admin.users.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Create customer</button>
        </div>
    </form>
@endsection