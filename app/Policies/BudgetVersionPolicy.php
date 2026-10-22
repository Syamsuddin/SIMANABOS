<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\User;

class BudgetVersionPolicy
{
    /** Role sekolah hanya melihat milik sekolahnya (SchoolScope); dinas melihat semua. */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::AdminDinas->value,
            RoleName::PemeriksaDinas->value,
            RoleName::PimpinanDinas->value,
            RoleName::OperatorSekolah->value,
            RoleName::KepalaSekolah->value,
        ]);
    }

    /** Isi RKAS hanya operator sekolah (docs/05), selama tahun anggaran terbuka. */
    public function create(User $user, FiscalYear $fiscalYear): bool
    {
        return $user->hasRole(RoleName::OperatorSekolah->value)
            && $user->school_id !== null
            && $fiscalYear->status === FiscalYearStatus::Open;
    }
}
