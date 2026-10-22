<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\PeriodicReport;
use App\Models\User;

class PeriodicReportPolicy
{
    /** Dinas melihat semua; role sekolah hanya milik sekolahnya (SchoolScope). */
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

    /** Mengisi angka pembanding: operator sekolah, tahun anggaran terbuka. */
    public function save(User $user, FiscalYear $fiscalYear): bool
    {
        return $user->hasRole(RoleName::OperatorSekolah->value)
            && $user->school_id !== null
            && $fiscalYear->status === FiscalYearStatus::Open;
    }

    /** Unggah/ganti/hapus dokumen pada laporan periodik yang sudah ada. */
    public function update(User $user, PeriodicReport $report): bool
    {
        return $user->school_id === $report->school_id && $this->save($user, $report->fiscalYear);
    }
}
