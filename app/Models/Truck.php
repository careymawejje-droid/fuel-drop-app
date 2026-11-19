<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Truck extends Model
{
    protected $fillable = [
        'provider_id',
        'branch_id',
        'name',
        'plate',
        'capacity_litres',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity_litres' => 'decimal:3',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
