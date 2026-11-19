@props(['status', 'paymentStatus' => null])

@php
    $label = $status === 'out_for_delivery' && $paymentStatus === 'paid'
        ? 'Awaiting Customer Confirmation'
        : match ($status) {
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'assigned' => 'Driver Assigned',
        'fuel_loading' => 'Fuel Loading',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
@endphp

<span {{ $attributes->merge(['class' => '']) }}>{{ $label }}</span>
