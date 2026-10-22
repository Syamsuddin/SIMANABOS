<?php

namespace App\Policies;

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\ExpenseType;
use App\Models\FiscalYear;
use App\Models\User;

class ExpenseTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::AdminDinas->value);
    }

    /** Jenis belanja hanya bisa ditambah atau diubah selama tahun anggaran masih terbuka. */
    public function create(User $user, FiscalYear $fiscalYear): bool
    {
        return $this->viewAny($user) && $fiscalYear->status === FiscalYearStatus::Open;
    }

    public function update(User $user, ExpenseType $expenseType): bool
    {
        return $this->create($user, $expenseType->fiscalYear);
    }
}
