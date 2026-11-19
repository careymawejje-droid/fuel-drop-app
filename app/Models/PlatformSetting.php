<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'contact_email',
        'delivery_fee',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['setting_key' => 'platform'],
            [
                'contact_email' => config('fueldrop.contact_email'),
                'delivery_fee' => config('fueldrop.delivery_fee', 0),
            ],
        );
    }
}
