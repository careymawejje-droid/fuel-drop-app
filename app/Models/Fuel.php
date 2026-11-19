<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fuel extends Model
{
    protected $fillable = [
        'name',
        'price_per_litre',
        'status',
    ];

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'fuel_order')->withPivot('branch_fuel_id', 'quantity_litres', 'unit_price')->withTimestamps();
    }

    public function branchFuel(): HasMany
    {
        return $this->hasMany(BranchFuel::class);
    }
}
