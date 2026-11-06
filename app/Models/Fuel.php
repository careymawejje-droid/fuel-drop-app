<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fuel extends Model
{
    protected $fillable = [
        'name',
        'price_per_litre',
        'status',
    ];
    
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'fuel_order')->withPivot('quantity_litres', 'unit_price')->withTimestamps();
    }
}
