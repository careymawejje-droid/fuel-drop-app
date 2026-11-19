@extends('layouts.fueldrop')

@section('title', 'Confirm password')

@section('content')
    <section class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-2xl font-bold">{{ __('Confirm your password') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}" class="mt-5">
            @csrf
            <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            <button type="submit" class="mt-4 rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
                {{ __('Confirm') }}
            </button>
        </form>
    </section>
@endsection
