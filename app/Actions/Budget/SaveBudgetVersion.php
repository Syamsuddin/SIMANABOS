<?php

namespace App\Actions\Budget;

use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Membuat versi RKAS baru (versi lama tidak pernah diubah, docs/06 §B).
 * Versi 1 tanpa alasan; versi berikutnya wajib beralasan (divalidasi di FormRequest).
 */
class SaveBudgetVersion
{
    /**
     * @param  array<int, string>  $lines  expense_type_id => jumlah (string desimal)
     */
    public function __invoke(
        School $school,
        FiscalYear $fiscalYear,
        FundSource $fundSource,
        string $effectiveDate,
        ?string $reason,
        array $lines,
    ): BudgetVersion {
        return DB::transaction(function () use ($school, $fiscalYear, $fundSource, $effectiveDate, $reason, $lines) {
            $latest = BudgetVersion::query()->withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('fund_source_id', $fundSource->id)
                ->lockForUpdate()
                ->max('version_no');

            $versionNo = ((int) $latest) + 1;

            $version = BudgetVersion::query()->withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'fiscal_year_id' => $fiscalYear->id,
                'fund_source_id' => $fundSource->id,
                'version_no' => $versionNo,
                'effective_date' => $effectiveDate,
                'reason' => $versionNo === 1 ? null : $reason,
            ]);

            foreach ($lines as $expenseTypeId => $amount) {
                $version->lines()->create(['expense_type_id' => $expenseTypeId, 'amount' => $amount]);
            }

            return $version;
        });
    }
}
