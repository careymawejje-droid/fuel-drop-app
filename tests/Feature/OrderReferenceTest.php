<?php

use App\Models\Order;
use App\Models\User;
use App\Support\OrderReference;

it('generates unique eight-character references while preserving internal ids and explicit historical references', function () {
    $customer = User::factory()->create();
    $orders = collect(range(1, 10))->map(fn () => Order::create([
        'user_id' => $customer->id,
        'total' => 1000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
    ]));

    expect($orders->pluck('order_number')->unique())->toHaveCount(10);
    foreach ($orders as $order) {
        expect($order->order_number)->toMatch('/\AFD[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}\z/')
            ->and(strlen($order->order_number))->toBeLessThanOrEqual(8)
            ->and(Order::query()->findOrFail($order->id)->order_number)->toBe($order->order_number);
    }

    $historical = Order::create([
        'order_number' => 'FD-LEGACY-REFERENCE-2001',
        'user_id' => $customer->id,
        'total' => 1000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
    ]);

    expect($historical->order_number)->toBe('FD-LEGACY-REFERENCE-2001');
});

it('retries a reference candidate that already exists', function () {
    $customer = User::factory()->create();
    Order::create([
        'order_number' => 'FDAAAAAA',
        'user_id' => $customer->id,
        'total' => 1000,
        'delivery_address' => 'Kampala',
        'status' => 'pending',
    ]);
    $candidates = ['FDAAAAAA', 'FDBBBBBB'];

    $reference = OrderReference::generate(function () use (&$candidates): string {
        return array_shift($candidates);
    });

    expect($reference)->toBe('FDBBBBBB')
        ->and($candidates)->toBe([]);
});
