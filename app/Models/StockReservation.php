<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    protected $fillable = [
        'order_id',
        'branch_fuel_id',
        'quantity_litres',
        'status',
        'reserved_at',
        'released_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_litres' => 'decimal:3',
            'reserved_at' => 'datetime',
            'released_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function branchFuel(): BelongsTo
    {
        return $this->belongsTo(BranchFuel::class);
    }
}
