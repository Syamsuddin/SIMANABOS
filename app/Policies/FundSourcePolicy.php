<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FundSource;
use App\Models\User;

class FundSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FundSource $fundSource): bool
    {
        return $this->viewAny($user);
    }
}
