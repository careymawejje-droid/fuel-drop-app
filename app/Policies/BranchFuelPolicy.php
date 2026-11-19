<?php

namespace App\Policies;

use App\Models\BranchFuel;
use App\Models\User;

class BranchFuelPolicy
{
    public function view(User $user, BranchFuel $branchFuel): bool
    {
        return $this->manage($user, $branchFuel);
    }

    public function manage(User $user, BranchFuel $branchFuel): bool
    {
        return app(ProviderPolicy::class)->manageInventory($user, $branchFuel->branch->provider);
    }
}
