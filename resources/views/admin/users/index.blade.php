{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Users')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-200 hover:text-white">Admin dashboard</a>
@endsection

@section('content')
    @php
        $statusStyles = ['active' => 'bg-emerald-100 text-emerald-800', 'inactive' => 'bg-slate-200 text-slate-700'];
    @endphp

    {{-- Page heading with the Add customer button --}}
    <section class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Users</h1>
            <p class="mt-1 text-slate-600">Review customer, provider, driver, and admin accounts.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-amber-400 px-4 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Add customer</a>
    </section>

    {{-- Success message after an action --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-sm font-medium">Search
            <input name="q" value="{{ $search }}" maxlength="100" placeholder="Name, email, or phone" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal">
        </label>
        <label class="text-sm font-medium">Role
            <select name="role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal">
                <option value="">All roles</option>
                @foreach ($roleLabels as $value => $label)
                    <option value="{{ $value }}" @selected($roleFilter === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium">Account status
            <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal">
                <option value="">All accounts</option>
                <option value="active" @selected($statusFilter === 'active')>Active</option>
                <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Filter</button>
            @if ($search !== '' || $roleFilter !== '' || $statusFilter !== '')
                <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a>
            @endif
        </div>
    </form>

    {{-- Users table --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-3 py-3 font-medium">Email</th>
                        <th class="px-3 py-3 font-medium">Role</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-semibold">{{ $user->name }}</td>
                            <td class="px-3 py-4 text-slate-600">{{ $user->email }}</td>

                            {{-- Roles, with underscores turned into spaces --}}
                            <td class="px-3 py-4 text-slate-600">{{ $user->roles->pluck('name')->map(fn ($role) => $roleLabels[$role] ?? str($role)->replace('_', ' ')->title())->join(', ') ?: 'No role' }}</td>

                            {{-- Account status as a coloured badge --}}
                            <td class="px-3 py-4">
                                @php($active = $user->status === 'active')
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles[$active ? 'active' : 'inactive'] }}">{{ $active ? 'Active' : 'Inactive' }}</span>
                            </td>

                            {{-- Row action --}}
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('admin.users.show', $user) }}" class="font-semibold text-blue-700 hover:text-blue-900">Details</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="font-semibold text-blue-700 hover:text-blue-900">Change role</a>
                                    @if (auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('{{ $active ? 'Deactivate this account? The user will be signed out and will no longer be able to log in.' : 'Reactivate this account?' }}')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $active ? 'inactive' : 'active' }}">
                                            <button class="font-semibold {{ $active ? 'text-red-700 hover:text-red-900' : 'text-emerald-700 hover:text-emerald-900' }}">{{ $active ? 'Deactivate' : 'Reactivate' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No users match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
