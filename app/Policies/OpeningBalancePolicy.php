<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\OpeningBalance;
use App\Models\User;

class OpeningBalancePolicy
{
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

    /** Operator mengisi saldo awal manual; setelah diverifikasi pemeriksa, angka terkunci. */
    public function save(User $user, FiscalYear $fiscalYear, ?OpeningBalance $balance = null): bool
    {
        if (! $user->hasRole(RoleName::OperatorSekolah->value) || $user->school_id === null) {
            return false;
        }

        if ($fiscalYear->status !== FiscalYearStatus::Open) {
            return false;
        }

        return $balance === null || ($balance->isManual() && ! $balance->isVerified());
    }

    public function verify(User $user, OpeningBalance $balance): bool
    {
        return $user->hasRole(RoleName::PemeriksaDinas->value)
            && $balance->isManual()
            && ! $balance->isVerified();
    }
}
