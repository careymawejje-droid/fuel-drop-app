<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function collectedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'collected_by_driver_id');
    }

    protected $fillable = [
        'order_id',
        'collected_by_driver_id',
        'payment_method',
        'phone_number',
        'amount',
        'status',
        'collected_at',
    ];
}
