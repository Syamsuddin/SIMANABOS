<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class AppSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    public function update(User $user): bool
    {
        return $this->viewAny($user);
    }
}
