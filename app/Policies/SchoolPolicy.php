<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, School $school): bool
    {
        return $this->viewAny($user);
    }

    /** Profil sekolah sendiri: admin atau role sekolah pemilik. */
    public function view(User $user, School $school): bool
    {
        return $this->viewAny($user) || ($user->isSchoolUser() && $user->school_id === $school->id);
    }

    /** Alamat/kontak: admin dan operator sekolah sendiri (docs/05). */
    public function updateProfile(User $user, School $school): bool
    {
        return $this->viewAny($user)
            || ($user->hasRole(RoleName::OperatorSekolah->value) && $user->school_id === $school->id);
    }

    public function proposeOfficial(User $user, School $school): bool
    {
        return $this->updateProfile($user, $school);
    }
}
