@extends('layouts.fueldrop')
@section('title', 'Live deliveries')
@section('content')
<h1 class="text-3xl font-bold">Live deliveries</h1>
<p class="mt-2 text-slate-600">Active deliveries for {{ $provider->name }}. Location freshness is checked when you open a delivery.</p>
<form method="GET" action="{{ route('provider.providers.tracking.index', $provider) }}" class="mt-5 flex flex-wrap gap-3"><label for="tracking-order-reference" class="sr-only">Search live deliveries by order reference</label><input id="tracking-order-reference" name="q" value="{{ $search }}" maxlength="64" placeholder="Search by order reference" class="min-w-64 rounded-lg border border-slate-300 px-3 py-2"><button class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Search</button>@if($search !== '')<a href="{{ route('provider.providers.tracking.index', $provider) }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a>@endif</form>
<div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-4">Order</th><th class="p-4">Status</th><th class="p-4">Driver</th><th class="p-4">GPS freshness</th><th class="p-4">Tracking</th></tr></thead><tbody>
@forelse ($orders as $order)@php($loc = $order->latestTrackingLocation)<tr class="border-b last:border-0"><td class="p-4"><a class="font-semibold text-blue-700" href="{{ route('provider.providers.orders.show', [$provider, $order]) }}">{{ $order->order_number }}</a></td><td class="p-4">{{ str_replace('_', ' ', ucfirst($order->status)) }}</td><td class="p-4">{{ $order->driver?->user?->name ?? 'Not assigned' }}</td><td class="p-4">@if(!$loc)Missing GPS @elseif($loc->recorded_at->lt(now()->subMinutes(2)))Stale · {{ $loc->recorded_at->diffForHumans() }}@else Fresh · {{ $loc->recorded_at->diffForHumans() }}@endif</td><td class="p-4"><a class="text-blue-700" href="{{ route('provider.providers.tracking.show', [$provider, $order]) }}">Open map</a></td></tr>
@empty<tr><td class="p-4 text-slate-500" colspan="4">No active deliveries.</td></tr>@endforelse
</tbody></table></div>
@endsection
