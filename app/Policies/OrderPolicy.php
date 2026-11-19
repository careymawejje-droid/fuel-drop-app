<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('admin') && ($user->can('view all orders') || $user->can('manage orders'))) {
            return true;
        }

        if ($user->hasRole('customer') && $user->can('view own orders') && $order->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('driver')
            && $user->can('view assigned deliveries')
            && $user->driver?->id === $order->driver_id) {
            return true;
        }

        return $order->provider !== null
            && app(ProviderPolicy::class)->view($user, $order->provider);
    }

    public function updateDeliveryStatus(User $user, Order $order): bool
    {
        return $user->hasRole('driver')
            && $user->can('update assigned delivery status')
            && $user->driver?->id === $order->driver_id;
    }

    public function collectPayment(User $user, Order $order): bool
    {
        return $order->status === 'out_for_delivery'
            && $user->hasRole('driver')
            && $user->can('record cash collections')
            && $user->driver?->id === $order->driver_id
            && $order->payments()->count() === 1
            && $order->payments()
                ->where('payment_method', 'cash_on_delivery')
                ->where('status', 'pending')
                ->where('amount', $order->total)
                ->whereNull('collected_at')
                ->whereNull('collected_by_driver_id')
                ->exists();
    }

    public function confirmDelivery(User $user, Order $order): bool
    {
        return $order->status === 'out_for_delivery'
            && $user->hasRole('customer')
            && $user->can('confirm deliveries')
            && $order->user_id === $user->id
            && $order->payments()->count() === 1
            && $order->payments()
                ->where('payment_method', 'cash_on_delivery')
                ->where('status', 'paid')
                ->where('amount', $order->total)
                ->where('collected_by_driver_id', $order->driver_id)
                ->whereNotNull('collected_at')
                ->exists();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasRole('customer')
            && $user->can('place orders')
            && $user->can('view own orders')
            && $order->user_id === $user->id
            && in_array($order->status, ['pending', 'accepted', 'assigned'], true);
    }
}
