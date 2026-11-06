<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'vehicle_id',
        'driver_id',
        'total',
        'delivery_address',
        'status',
        'placed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function fuels()
    {
        return $this->belongsToMany(Fuel::class, 'fuel_order')->withPivot('quantity_litres', 'unit_price')->withTimestamps();
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
