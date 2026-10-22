<?php

namespace App\Actions\Budget;

use App\Models\Allocation;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\OpeningBalance;
use App\Models\School;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;

/** Pembacaan RKAS berversi: versi berlaku per bulan, total, dan peringatan pagu (docs/04, docs/06 §B). */
class BudgetSummary
{
    /**
     * Versi berlaku pada hari terakhir bulan itu (docs/04 §Aturan agregasi antarperiode).
     */
    public function versionForMonth(School $school, FiscalYear $fiscalYear, FundSource $fundSource, int $month): ?BudgetVersion
    {
        $endOfMonth = Carbon::create($fiscalYear->year, $month, 1)->endOfMonth()->toDateString();

        return BudgetVersion::query()->withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('fund_source_id', $fundSource->id)
            ->whereDate('effective_date', '<=', $endOfMonth)
            ->orderByDesc('version_no')
            ->first();
    }

    public function total(BudgetVersion $version): string
    {
        $sum = BigDecimal::zero();
        foreach ($version->lines()->pluck('amount') as $amount) {
            $sum = $sum->plus($amount);
        }

        return (string) $sum->toScale(2);
    }

    /**
     * Peringatan (tidak memblokir) bila total anggaran > pagu + saldo awal dana.
     *
     * @return array{total: string, ceiling: string}|null
     */
    public function ceilingWarning(BudgetVersion $version): ?array
    {
        $allocation = Allocation::query()->withoutGlobalScopes()
            ->where('school_id', $version->school_id)
            ->where('fiscal_year_id', $version->fiscal_year_id)
            ->where('fund_source_id', $version->fund_source_id)
            ->value('amount');

        $opening = OpeningBalance::query()->withoutGlobalScopes()
            ->where('school_id', $version->school_id)
            ->where('fiscal_year_id', $version->fiscal_year_id)
            ->where('fund_source_id', $version->fund_source_id)
            ->value('fund_amount');

        $ceiling = BigDecimal::of($allocation ?? '0')->plus($opening ?? '0')->toScale(2);
        $total = BigDecimal::of($this->total($version));

        return $total->isGreaterThan($ceiling)
            ? ['total' => (string) $total, 'ceiling' => (string) $ceiling]
            : null;
    }
}
