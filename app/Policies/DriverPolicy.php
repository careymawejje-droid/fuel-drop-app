<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    public function view(User $user, Driver $driver): bool
    {
        return ($user->hasRole('driver')
                && $user->can('view assigned deliveries')
                && $driver->user_id === $user->id)
            || ($driver->provider !== null
                && app(ProviderPolicy::class)->manageDrivers($user, $driver->provider));
    }

    public function manage(User $user, Driver $driver): bool
    {
        return $driver->provider !== null
            && app(ProviderPolicy::class)->manageDrivers($user, $driver->provider);
    }
}
