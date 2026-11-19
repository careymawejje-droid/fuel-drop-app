{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Edit user')

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
        <h1 class="text-3xl font-bold">Edit user</h1>
    </section>

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- User form: same route, method and field names as before --}}
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf
        @method('PUT')

        {{-- Section 1: account details --}}
        <div class="border-b border-slate-200 p-6">
            <h2 class="font-bold">Account details</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium sm:col-span-2">Full name
                    <input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Email
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Phone
                    <input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="20" autocomplete="tel" class="{{ $input }}">
                </label>

                {{-- Role and account status: read-only, shown as badges; they are not submitted with the form --}}
                <div class="text-sm">
                    <span class="block font-medium">Role</span>
                    <p class="mt-1 inline-flex rounded-full bg-slate-100 px-3 py-2 font-semibold text-slate-700">{{ $user->roles->pluck('name')->join(', ') ?: 'No role' }}</p>
                </div>
                <div class="text-sm">
                    <span class="block font-medium">Account status</span>
                    <p class="mt-1 inline-flex rounded-full bg-slate-100 px-3 py-2 font-semibold text-slate-700">{{ ucfirst($user->status) }}</p>
                </div>
            </div>
        </div>

        {{-- Section 2: optional password change --}}
        <div class="p-6">
            <h2 class="font-bold">Change password</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">New password <span class="font-normal text-slate-500">(optional)</span>
                    <input name="password" type="password" minlength="8" autocomplete="new-password" class="{{ $input }}">
                </label>
                <label class="block text-sm font-medium">Confirm new password
                    <input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" class="{{ $input }}">
                </label>
            </div>
        </div>

        {{-- Actions: cancel returns to the users list --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('admin.users.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
            <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save changes</button>
        </div>
    </form>

    <section class="mt-6 grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="font-bold">Role and permissions</h2>
            <p class="mt-1 text-sm text-slate-600">Current role: {{ $user->roles->pluck('name')->map(fn ($role) => $roleLabels[$role] ?? $role)->join(', ') ?: 'No role' }}. Saving replaces previous role assignments and clears direct permissions.</p>
            @if (auth()->id() === $user->id && $user->hasRole('admin'))
                <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">You cannot change your own Admin role.</p>
            @else
                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="mt-4" onsubmit="return confirm('Change this user’s role and replace their current role permissions?')">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm font-medium">New role
                        <select name="role" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                            @foreach ($roleLabels as $value => $label)
                                <option value="{{ $value }}" @selected($user->roles->count() === 1 && $user->roles->first()->name === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="mt-4 rounded-lg bg-amber-400 px-4 py-2 font-bold text-slate-900">Change role</button>
                </form>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="font-bold">Account access</h2>
            <p class="mt-1 text-sm text-slate-600">Current status: <strong>{{ $user->status === 'active' ? 'Active' : 'Inactive' }}</strong>. Deactivation keeps this user and their records.</p>
            @if (auth()->id() === $user->id)
                <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">You cannot deactivate your own account.</p>
            @else
                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="mt-4" onsubmit="return confirm('{{ $user->status === 'active' ? 'Deactivate this account? Existing access will be revoked.' : 'Reactivate this account?' }}')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'inactive' : 'active' }}">
                    <button class="rounded-lg px-4 py-2 font-bold text-white {{ $user->status === 'active' ? 'bg-red-700 hover:bg-red-800' : 'bg-emerald-700 hover:bg-emerald-800' }}">{{ $user->status === 'active' ? 'Deactivate account' : 'Reactivate account' }}</button>
                </form>
            @endif
        </div>
    </section>
@endsection
