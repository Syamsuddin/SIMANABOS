<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\SchoolOfficial;
use App\Models\User;

class SchoolOfficialPolicy
{
    public function approve(User $user, SchoolOfficial $official): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    /** Menolak usulan yang belum disahkan (baris sah tidak pernah dihapus). */
    public function reject(User $user, SchoolOfficial $official): bool
    {
        return $official->isPending() && $this->approve($user, $official);
    }
}
