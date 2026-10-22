<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\User;

class FiscalYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    public function view(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Jadwal (tahap, tenggat) hanya bisa diubah selama tahun masih terbuka. */
    public function update(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->viewAny($user) && $fiscalYear->status === FiscalYearStatus::Open;
    }

    /** Tutup tahun: hanya admin dinas, hanya tahun yang masih terbuka (docs/05). */
    public function close(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->update($user, $fiscalYear);
    }
}
