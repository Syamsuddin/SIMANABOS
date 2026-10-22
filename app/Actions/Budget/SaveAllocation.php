<?php

namespace App\Actions\Budget;

use App\Models\Allocation;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan pagu sekolah × sumber dana dan membuat laporan `draf` untuk bulan
 * `active_from_month`..12 (idempoten; laporan yang sudah ada tidak disentuh, docs/06 §A).
 */
class SaveAllocation
{
    public function __invoke(School $school, FiscalYear $fiscalYear, FundSource $fundSource, string $amount, int $activeFromMonth): Allocation
    {
        return DB::transaction(function () use ($school, $fiscalYear, $fundSource, $amount, $activeFromMonth) {
            $keys = [
                'school_id' => $school->id,
                'fiscal_year_id' => $fiscalYear->id,
                'fund_source_id' => $fundSource->id,
            ];

            $allocation = Allocation::query()->withoutGlobalScopes()->updateOrCreate(
                $keys,
                ['amount' => $amount, 'active_from_month' => $activeFromMonth],
            );

            foreach (range($activeFromMonth, 12) as $month) {
                MonthlyReport::query()->withoutGlobalScopes()->firstOrCreate([...$keys, 'month' => $month]);
            }

            return $allocation;
        });
    }
}
