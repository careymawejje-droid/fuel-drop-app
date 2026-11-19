<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\OrderReference;

class Order extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (! filled($order->order_number)) {
                $order->order_number = OrderReference::generate();
            }
        });
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'provider_id',
        'branch_id',
        'vehicle_id',
        'delivery_address_id',
        'driver_id',
        'truck_id',
        'total',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'status',
        'rejection_reason',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
        ];
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('customer')) {
            return $query->where('user_id', $user->id);
        }

        if ($user->hasRole('driver')) {
            return $query->where('driver_id', $user->driver?->id ?? 0);
        }

        if ($user->hasRole('provider')) {
            return $query->whereIn('provider_id', Provider::query()->accessibleBy($user)->select('providers.id'));
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeSearchReference(Builder $query, ?string $reference): Builder
    {
        $reference = trim((string) $reference);

        return $reference === ''
            ? $query
            : $query->where('order_number', 'like', '%'.mb_strtoupper($reference).'%');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function fuels(): BelongsToMany
    {
        return $this->belongsToMany(Fuel::class, 'fuel_order')->withPivot('branch_fuel_id', 'quantity_litres', 'unit_price')->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }
}
