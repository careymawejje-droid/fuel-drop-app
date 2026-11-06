<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'truck_plate',
        'available_status',
        'current_latitude',
        'current_longitude',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function locations()
    {
        return $this->hasMany(DriverLocation::class);
    }
}
