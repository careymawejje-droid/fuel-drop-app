@extends('layouts.fueldrop')

@section('title', 'User details')

@section('back-link')
    <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-200 hover:text-white">Users</a>
@endsection

@section('content')
    <section class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-wide text-amber-700">User account</p>
            <h1 class="mt-1 text-3xl font-bold">{{ $user->name }}</h1>
            <p class="mt-1 text-slate-600">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-amber-400 px-4 py-2.5 font-bold text-slate-900">Edit account and role</a>
    </section>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Account details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Phone</dt><dd class="mt-1 font-medium">{{ $user->phone ?: 'Not provided' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Email verification</dt><dd class="mt-1 font-medium">{{ $user->email_verified_at ? 'Verified' : 'Not verified' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Role</dt><dd class="mt-1 font-medium">{{ $user->roles->pluck('name')->map(fn ($role) => $roleLabels[$role] ?? $role)->join(', ') ?: 'No role' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Account status</dt><dd class="mt-1"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $user->status === 'active' ? 'Active' : 'Inactive' }}</span></dd></div>
                <div><dt class="text-sm text-slate-500">Registered</dt><dd class="mt-1 font-medium">{{ $user->created_at?->format('d M Y H:i') }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">Existing business records</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Orders</dt><dd class="mt-1 font-medium">{{ $user->orders()->count() }}</dd></div>
                <div><dt class="text-sm text-slate-500">Driver profile</dt><dd class="mt-1 font-medium">{{ $user->driver ? 'Present · '.$user->driver->available_status : 'None' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Provider memberships</dt><dd class="mt-1 font-medium">{{ $user->providers->pluck('name')->join(', ') ?: 'None' }}</dd></div>
            </dl>
            <p class="mt-4 text-sm text-slate-500">Changing a role does not remove provider memberships, driver profiles, orders, payments, or delivery history.</p>
        </div>
    </section>
@endsection
