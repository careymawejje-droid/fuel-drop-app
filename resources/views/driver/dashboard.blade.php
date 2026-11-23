<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDrop | Deliveries</title>
    <meta name="description" content="Order petrol, diesel or paraffin and follow the delivery driver live on the map.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    {{-- Reuse shared palette and spacing for the driver dashboard. --}}
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-navy font-sans antialiased" data-page="">
    @include('components.fueldrop.sidebar', ['role' => 'driver'])
        <main class="min-h-screen p-6 md:p-10 lg:ml-64">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-8">
                    <h1 class="text-3xl font-bold">Delivery dashboard</h1>
                    <a href="{{ route('driver.deliveries.index') }}" class="inline-block rounded-lg px-5 py-2.5 font-bold text-center bg-brand-500 text-navy">All deliveries</a>
                </div>
                {{-- Assigned order count is limited by the driver's relationship scope. --}}
                <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
                    <p class="text-sm text-slate-500">Assigned deliveries</p>
                    <p class="mt-1 text-3xl font-bold">{{ $ordersCount }}</p>
                </div>

                {{-- Read-only delivery summaries; status changes are not implemented yet. --}}
                <div class="grid md:grid-cols-2 gap-6">
                    @forelse ($orders as $order)
                        <article class="bg-white border border-slate-200 rounded-2xl p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h2 class="text-xl font-bold">{{ $order->order_number }}</h2>
                                <x-order-status :status="$order->status" :payment-status="$order->payment?->status" class="rounded-full px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800" />
                            </div>
                            <p class="text-slate-500 mt-2">{{ $order->delivery_address }}</p>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-slate-500">Customer</dt><dd class="mt-1 font-semibold">{{ $order->customer?->name ?? 'Customer unavailable' }}</dd></div>
                                <div><dt class="text-slate-500">Truck</dt><dd class="mt-1 font-semibold">{{ $order->truck?->plate ?? 'Not assigned' }}</dd></div>
                            </dl>
                            <a href="{{ route('driver.deliveries.show', $order) }}" class="mt-5 inline-flex w-full justify-center rounded-lg border border-navy py-2.5 font-bold">View delivery</a>
                        </article>
                    @empty
                        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center md:col-span-2">
                            <i data-lucide="package-check" class="mx-auto h-8 w-8 text-slate-400"></i>
                            <h2 class="mt-3 text-lg font-bold">No assigned deliveries</h2>
                            <p class="mt-1 text-sm text-slate-500">Assigned orders will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </main>
    <script>lucide.createIcons();</script>
</body>
</html>
