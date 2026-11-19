@extends('layouts.fueldrop')

@section('title', 'Notifications')

@section('back-link')
    <a href="{{ route('home') }}" class="text-sm text-slate-200 hover:text-white">Dashboard</a>
@endsection

@section('content')
    <section class="mb-6">
        <p class="text-sm font-bold uppercase tracking-wide text-amber-700">Updates</p>
        <h1 class="mt-1 text-3xl font-bold">Notifications</h1>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                <article class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                    <div class="flex items-start gap-4">
                        <i data-lucide="{{ $notification->read_at ? 'bell' : 'bell-ring' }}" class="mt-1 h-5 w-5 shrink-0 text-amber-600"></i>
                        <div>
                            <p class="font-semibold {{ $notification->read_at ? 'text-slate-600' : 'text-slate-900' }}">{{ $notification->title }}</p>
                            @if ($notification->order)
                                <p class="mt-1 text-sm text-slate-500">Order {{ $notification->order->order_number }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Mark as read</button>
                        </form>
                    @else
                        <span class="text-xs font-medium text-slate-400">Read</span>
                    @endif
                </article>
            @empty
                <div class="py-10 text-center text-slate-500">You have no notifications.</div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="mt-5 border-t border-slate-100 pt-4">{{ $notifications->links() }}</div>
        @endif
    </section>
@endsection
