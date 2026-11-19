<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Provider extends Model
{
    protected $fillable = [
        'admin_user_id',
        'name',
        'phone',
        'email',
        'status',
    ];

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if (! $user->hasRole('provider')) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('providers.status', 'active')
            ->whereHas('users', function (Builder $providerUsers) use ($user): void {
                $providerUsers
                    ->whereKey($user->id)
                    ->where('provider_user.is_active', true);
            });
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_active')->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->users()
            ->wherePivot('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'provider'));
    }

    public function notifyActiveMembers(string $title, ?Order $order = null, ?User $except = null): void
    {
        $members = $this->activeMembers();

        if ($except !== null) {
            $members->where('users.id', '!=', $except->id);
        }

        foreach ($members->get() as $member) {
            $member->notifications()->create([
                'order_id' => $order?->id,
                'title' => $title,
            ]);
        }
    }

    public function designatedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function branchFuel(): HasManyThrough
    {
        return $this->hasManyThrough(BranchFuel::class, Branch::class);
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
