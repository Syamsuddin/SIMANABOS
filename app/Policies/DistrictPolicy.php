<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\District;
use App\Models\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, District $district): bool
    {
        return $this->viewAny($user);
    }
}
