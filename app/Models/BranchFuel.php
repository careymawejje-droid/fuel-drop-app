<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchFuel extends Model
{
    protected $table = 'branch_fuel';

    protected $fillable = [
        'branch_id',
        'fuel_id',
        'price_per_litre',
        'available_stock_litres',
        'reserved_stock_litres',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price_per_litre' => 'decimal:2',
            'available_stock_litres' => 'decimal:3',
            'reserved_stock_litres' => 'decimal:3',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fuel(): BelongsTo
    {
        return $this->belongsTo(Fuel::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }
}
