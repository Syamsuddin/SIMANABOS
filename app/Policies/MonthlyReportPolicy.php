<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\MonthlyReport;
use App\Models\User;

class MonthlyReportPolicy
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

    public function view(User $user, MonthlyReport $report): bool
    {
        return $this->viewAny($user)
            && (! $user->isSchoolUser() || $user->school_id === $report->school_id);
    }

    /** Jejak audit: admin dan pemeriksa semua, role sekolah milik sendiri, pimpinan tidak (docs/05). */
    public function viewAudit(User $user, MonthlyReport $report): bool
    {
        return $this->view($user, $report) && ! $user->hasRole(RoleName::PimpinanDinas->value);
    }

    /** Angka diisi operator sekolah sendiri, hanya pada status draf/perlu perbaikan dan tahun terbuka (docs/05, 06 §D). */
    public function update(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::OperatorSekolah->value)
            && $user->school_id === $report->school_id
            && $report->isEditable()
            && $report->fiscalYear->status === FiscalYearStatus::Open;
    }

    /** Ajukan (draf) atau ajukan ulang (perlu perbaikan): operator sekolah sendiri, tahun terbuka. */
    public function submit(User $user, MonthlyReport $report): bool
    {
        return $this->update($user, $report);
    }

    /** Tarik kembali hanya selama belum diambil pemeriksa (guard di ReportStateMachine). */
    public function withdraw(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::OperatorSekolah->value)
            && $user->school_id === $report->school_id
            && $report->status === ReportStatus::Diajukan;
    }

    public function approve(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::KepalaSekolah->value)
            && $user->school_id === $report->school_id;
    }

    /** Operator menanggapi catatan saat laporan perlu perbaikan. */
    public function respond(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::OperatorSekolah->value)
            && $user->school_id === $report->school_id
            && $report->status === ReportStatus::PerluPerbaikan;
    }

    public function claim(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::PemeriksaDinas->value) && $report->status === ReportStatus::Diajukan;
    }

    /** Lepas, catat, minta perbaikan, selesaikan catatan, nyatakan selesai: hanya pemeriksa yang mengambil. */
    public function review(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::PemeriksaDinas->value)
            && $report->status === ReportStatus::DalamPemeriksaan
            && $report->reviewer_id === $user->id;
    }

    public function unlock(User $user, MonthlyReport $report): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value) && $report->status === ReportStatus::SelesaiDiperiksa;
    }
}
