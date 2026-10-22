<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\User;

class AllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    /** Pagu hanya diisi admin selama tahun anggaran masih terbuka. */
    public function manage(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->viewAny($user) && $fiscalYear->status === FiscalYearStatus::Open;
    }
}
