<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function view(User $user, Branch $branch): bool
    {
        return $this->manage($user, $branch);
    }

    public function manage(User $user, Branch $branch): bool
    {
        return app(ProviderPolicy::class)->manageBranches($user, $branch->provider);
    }
}
