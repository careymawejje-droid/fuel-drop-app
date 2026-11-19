{{-- Uses the shared FuelDrop layout; only the sections below are restyled --}}
@extends('layouts.fueldrop')

@section('title', 'Edit provider')

{{-- Back link shown in the layout's header --}}
@section('back-link')
    <a href="{{ route('admin.providers.index') }}" class="text-sm text-slate-200 hover:text-white">Providers</a>
@endsection

@section('content')
    {{-- Page heading --}}
    <section class="mb-6">
        <h1 class="text-3xl font-bold">Edit {{ $provider->name }}</h1>
    </section>

    {{-- Success message after saving --}}
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    {{-- Error display: first validation error --}}
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Provider details form: same route, method and field names as before --}}
    <form method="POST" action="{{ route('admin.providers.update', $provider) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @csrf
        @method('PUT')

        {{-- Fields --}}
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <label class="block text-sm font-medium">Provider name
                <input name="name" value="{{ old('name', $provider->name) }}" required maxlength="255"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            </label>
            <label class="block text-sm font-medium">Status
                <select name="status" required
                        class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    @foreach (['active', 'suspended', 'inactive'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $provider->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">Phone
                <input name="phone" value="{{ old('phone', $provider->phone) }}" maxlength="20" autocomplete="tel"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            </label>
            <label class="block text-sm font-medium">Email
                <input name="email" type="email" value="{{ old('email', $provider->email) }}" maxlength="255" autocomplete="email"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
            </label>
        </div>

        {{-- Actions: workspace link only for users allowed to view the provider --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <div>
                @can('view', $provider)
                    <a href="{{ route('provider.providers.show', $provider) }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">Open workspace</a>
                @endcan
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.providers.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900">Cancel</a>
                <button class="rounded-lg bg-amber-400 px-5 py-2.5 font-bold text-slate-900 transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Save provider</button>
            </div>
        </div>
    </form>

    {{-- Designated Provider account: who it is, and what an admin can do with it --}}
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 text-sm">
        <h2 class="font-bold">Designated Provider account</h2>

        @if ($provider->designatedAdmin)
            {{-- Account exists: show name and email --}}
            <p class="mt-2 font-medium">{{ $provider->designatedAdmin->name }}</p>
            <p class="text-slate-600">{{ $provider->designatedAdmin->email }}</p>

            {{-- Link to the account's profile, only with both user permissions --}}
            @if (auth()->user()->can('manage users') && auth()->user()->can('manage platform users'))
                <a href="{{ route('admin.users.edit', $provider->admin_user_id) }}" class="mt-2 inline-block font-semibold text-blue-700 hover:text-blue-900">Manage account profile</a>
            @endif

            {{-- Access control: activate or suspend the account --}}
            @can('manage provider accounts')
                <form method="POST" action="{{ route('admin.providers.accounts.update', $provider) }}" class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-200 pt-4">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-medium">Account access
                        <select name="is_active" required
                                class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                            <option value="1" @selected($providerAccountIsActive)>Active</option>
                            <option value="0" @selected(! $providerAccountIsActive)>Suspended</option>
                        </select>
                    </label>
                    <button class="rounded-lg border border-slate-300 px-4 py-2 font-semibold transition hover:bg-slate-50">Update account access</button>
                </form>
            @endcan
        @else
            {{-- No account yet: empty state with the create action --}}
            <p class="mt-2 text-slate-600">No account is assigned yet.</p>
            @can('manage provider accounts')
                <a href="{{ route('admin.providers.accounts.create', $provider) }}" class="mt-2 inline-block font-semibold text-blue-700 hover:text-blue-900">Create designated Provider account</a>
            @endcan
        @endif
    </section>
@endsection