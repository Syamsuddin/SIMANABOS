<?php

namespace App\Services;

use App\Models\BudgetVersion;
use App\Models\MonthlyReport;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;

/**
 * Sisa anggaran = RKAS berlaku pada hari terakhir bulan laporan − realisasi neto kumulatif (docs/04).
 * Bukan saldo kas; selalu ditampilkan terpisah.
 */
class RemainingBudgetCalculator
{
    public function __construct(private readonly ReportBalanceCalculator $calculator) {}

    /** @return array{budget: ?string, realization: string, remaining: ?string} `budget` null bila belum ada RKAS berlaku. */
    public function forReport(MonthlyReport $report): array
    {
        $realization = BigDecimal::zero();
        MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->where('month', '<=', $report->month)
            ->with(['lines', 'adjustments'])->get()
            ->each(function (MonthlyReport $item) use (&$realization) {
                $realization = $realization->plus($this->calculator->forReport($item)['total_net_realization']);
            });

        $lastDay = Carbon::create($report->fiscalYear->year, $report->month, 1)->endOfMonth()->toDateString();
        $version = BudgetVersion::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->whereDate('effective_date', '<=', $lastDay)
            ->orderByDesc('version_no')->with('lines')->first();

        if ($version === null) {
            return ['budget' => null, 'realization' => (string) $realization, 'remaining' => null];
        }

        $budget = $version->lines->reduce(fn (BigDecimal $sum, $line) => $sum->plus($line->amount), BigDecimal::zero()->toScale(2));

        return ['budget' => (string) $budget, 'realization' => (string) $realization, 'remaining' => (string) $budget->minus($realization)];
    }
}
