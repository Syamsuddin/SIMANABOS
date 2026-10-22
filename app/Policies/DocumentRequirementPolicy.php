<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\DocumentRequirement;
use App\Models\FiscalYear;
use App\Models\User;

class DocumentRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    /** Aturan dokumen hanya diubah selama tahun anggaran masih terbuka. */
    public function create(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->viewAny($user) && $fiscalYear->status === FiscalYearStatus::Open;
    }

    public function update(User $user, DocumentRequirement $requirement): bool
    {
        return $this->create($user, $requirement->fiscalYear);
    }

    public function delete(User $user, DocumentRequirement $requirement): bool
    {
        return $this->update($user, $requirement);
    }
}
