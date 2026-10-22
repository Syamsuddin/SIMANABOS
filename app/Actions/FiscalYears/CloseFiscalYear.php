<?php

namespace App\Actions\FiscalYears;

use App\Actions\Reports\SaveReportFigures;
use App\Enums\FiscalYearStatus;
use App\Enums\ReportStatus;
use App\Exceptions\DomainRuleException;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\User;
use App\Services\ReportBalanceCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Tutup tahun anggaran (docs/06 §G): saldo akhir Desember per sekolah × sumber dana menjadi
 * `opening_balances` tahun berikut (`origin=carried_over`). Saldo dihitung ulang dari angka
 * laporan oleh ReportBalanceCalculator, bukan dibaca dari cache `computed_*`.
 */
class CloseFiscalYear
{
    public function __construct(
        private readonly ReportBalanceCalculator $calculator,
        private readonly SaveReportFigures $figures,
    ) {}

    /** @return int jumlah saldo awal tahun berikut yang ditetapkan */
    public function __invoke(FiscalYear $fiscalYear, User $actor, ?string $confirmYear = null): int
    {
        return DB::transaction(function () use ($fiscalYear, $actor, $confirmYear) {
            $fiscalYear = FiscalYear::query()->lockForUpdate()->findOrFail($fiscalYear->id);

            if ($fiscalYear->status !== FiscalYearStatus::Open) {
                throw new DomainRuleException([__('domain.fiscal_year.already_closed')]);
            }

            $next = FiscalYear::query()->where('year', $fiscalYear->year + 1)->first();
            if ($next === null) {
                throw new DomainRuleException([__('domain.fiscal_year.next_missing', ['year' => $fiscalYear->year + 1])]);
            }

            $decemberReports = MonthlyReport::query()->withoutGlobalScopes()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('month', 12)
                ->with(['lines', 'adjustments'])
                ->get();

            $unfinished = $decemberReports->contains(fn (MonthlyReport $r) => $r->status !== ReportStatus::SelesaiDiperiksa);
            if ($unfinished && trim((string) $confirmYear) !== (string) $fiscalYear->year) {
                throw new DomainRuleException([__('domain.fiscal_year.confirm_year', ['year' => $fiscalYear->year])]);
            }

            foreach ($decemberReports as $december) {
                $ending = $this->calculator->forReport($december);

                OpeningBalance::query()->withoutGlobalScopes()->updateOrCreate(
                    [
                        'school_id' => $december->school_id,
                        'fiscal_year_id' => $next->id,
                        'fund_source_id' => $december->fund_source_id,
                    ],
                    [
                        'fund_amount' => $ending['ending_fund'],
                        'tax_amount' => $ending['ending_tax'],
                        'origin' => OpeningBalance::ORIGIN_CARRIED_OVER,
                        'verified_by' => null,
                        'verified_at' => null,
                    ],
                );

                $january = MonthlyReport::query()->withoutGlobalScopes()
                    ->where('school_id', $december->school_id)
                    ->where('fiscal_year_id', $next->id)
                    ->where('fund_source_id', $december->fund_source_id)
                    ->where('month', 1)
                    ->first();
                if ($january !== null) {
                    $this->figures->recomputeFrom($january);
                }
            }

            $fiscalYear->forceFill([
                'status' => FiscalYearStatus::Closed,
                'closed_at' => now(),
                'closed_by' => $actor->id,
            ])->save();

            activity()->causedBy($actor)->performedOn($fiscalYear)
                ->withProperties(['carried_over' => $decemberReports->count(), 'forced' => $unfinished])
                ->log('fiscal_year.closed');

            return $decemberReports->count();
        });
    }
}
