<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('customer') && $user->can('manage own addresses');
    }

    public function view(User $user, Address $address): bool
    {
        return $user->hasRole('customer')
            && $user->can('manage own addresses')
            && $address->user_id === $user->id;
    }

    public function update(User $user, Address $address): bool
    {
        return $this->view($user, $address);
    }

    public function delete(User $user, Address $address): bool
    {
        return $this->view($user, $address);
    }
}
