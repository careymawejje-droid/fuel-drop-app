<?php

namespace App\Policies;

use App\Models\Truck;
use App\Models\User;

class TruckPolicy
{
    public function view(User $user, Truck $truck): bool
    {
        return $this->manage($user, $truck);
    }

    public function manage(User $user, Truck $truck): bool
    {
        return app(ProviderPolicy::class)->manageTrucks($user, $truck->provider);
    }
}
