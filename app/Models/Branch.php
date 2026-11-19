<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'provider_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'service_radius_metres',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function fuelInventory(): HasMany
    {
        return $this->hasMany(BranchFuel::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
