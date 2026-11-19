<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function view(User $user, Provider $provider): bool
    {
        if ($user->hasRole('admin')) {
            return $user->can('manage providers');
        }

        return $user->hasRole('provider')
            && $user->can('view own orders')
            && $this->isActiveMember($user, $provider);
    }

    public function manage(User $user, Provider $provider): bool
    {
        return $user->hasRole('admin')
            && $user->can('manage providers');
    }

    public function manageBranches(User $user, Provider $provider): bool
    {
        return $this->canManage($user, $provider, 'manage provider branches', 'manage providers');
    }

    public function manageInventory(User $user, Provider $provider): bool
    {
        return $this->canManage($user, $provider, 'manage provider inventory', 'manage fuels');
    }

    public function manageDrivers(User $user, Provider $provider): bool
    {
        return $this->canManage($user, $provider, 'manage provider drivers', 'manage drivers');
    }

    public function manageTrucks(User $user, Provider $provider): bool
    {
        return $this->canManage($user, $provider, 'manage provider trucks', 'manage providers');
    }

    public function manageOrders(User $user, Provider $provider): bool
    {
        return $this->canManage($user, $provider, 'manage provider orders', 'manage orders');
    }

    public function assignDeliveries(User $user, Provider $provider): bool
    {
        return $this->canManage($user, $provider, 'assign provider deliveries', 'manage providers');
    }

    private function canManage(User $user, Provider $provider, string $providerPermission, string $adminPermission): bool
    {
        if ($user->hasRole('admin')) {
            return $user->can($adminPermission);
        }

        return $user->hasRole('provider')
            && $user->can($providerPermission)
            && $this->isActiveMember($user, $provider);
    }

    private function isActiveMember(User $user, Provider $provider): bool
    {
        return $provider->status === 'active'
            && $user->providers()
                ->whereKey($provider->getKey())
                ->wherePivot('is_active', true)
                ->exists();
    }
}
