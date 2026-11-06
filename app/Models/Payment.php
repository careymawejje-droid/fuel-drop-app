<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'order_id',
        'payment_method',
        'phone_number',
        'amount',
        'status',  
    ];
}
