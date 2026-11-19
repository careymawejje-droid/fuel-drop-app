@extends('layouts.fueldrop')

@section('title', 'Verify email')

@section('content')
    <section class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-2xl font-bold">{{ __('Verify your email address') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('Before continuing, please verify your email address using the link we emailed to you. If you did not receive it, we will gladly send you another.') }}</p>

        @if (session('status') === 'verification-link-sent')
            <p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">
                {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
            </p>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <a href="{{ route('profile.show') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Edit Profile') }}</a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-3 py-2.5 text-sm font-semibold text-slate-600 underline hover:text-slate-900">{{ __('Log Out') }}</button>
            </form>
        </div>
    </section>
@endsection
