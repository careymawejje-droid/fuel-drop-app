<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected $fillable = [
        'driver_id',
        'order_id',
        'latitude',
        'longitude',
        'accuracy_metres',
        'recorded_at',
    ];
}
