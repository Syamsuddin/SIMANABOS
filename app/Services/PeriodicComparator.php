<?php

namespace App\Services;

use App\Enums\PeriodicKind;
use App\Enums\ReportStatus;
use App\Models\FiscalYearStage;
use App\Models\PeriodicReport;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

/**
 * Membandingkan angka laporan tahap/tahunan dengan Σ realisasi neto bulanan pada rentangnya (docs/23 F-09).
 * Murni baca: angka periodik tidak pernah masuk realisasi bulanan atau dasbor. Rumus realisasi neto
 * dipakai bersama dengan ReportAggregator agar tidak ada dua definisi.
 */
class PeriodicComparator
{
    /**
     * Null bila rentang tidak berlaku (berita acara, atau tahap belum didefinisikan Dinas).
     *
     * @return array{from: int, to: int, counted: int, months: int, monthly_total: string, reported: ?string, difference: ?string}|null
     */
    public function compare(PeriodicReport $report): ?array
    {
        $range = $this->range($report);
        if ($range === null) {
            return null;
        }
        [$from, $to] = $range;

        $row = DB::table('monthly_reports as r')
            ->where('r.school_id', $report->school_id)
            ->where('r.fiscal_year_id', $report->fiscal_year_id)
            ->where('r.fund_source_id', $report->fund_source_id)
            ->whereBetween('r.month', [$from, $to])
            ->where('r.status', '!=', ReportStatus::Draf->value)
            ->selectRaw('count(*) as counted, coalesce(sum('.ReportAggregator::NET_REALIZATION.'), 0) as total')
            ->first();

        $monthly = BigDecimal::of((string) $row->total)->toScale(2);
        $reported = $report->reported_total_realization === null ? null : BigDecimal::of($report->reported_total_realization)->toScale(2);

        return [
            'from' => $from,
            'to' => $to,
            'counted' => (int) $row->counted,
            'months' => $to - $from + 1,
            'monthly_total' => (string) $monthly,
            'reported' => $reported === null ? null : (string) $reported,
            'difference' => $reported === null ? null : (string) $reported->minus($monthly),
        ];
    }

    /** @return array{0: int, 1: int}|null */
    private function range(PeriodicReport $report): ?array
    {
        return match ($report->kind) {
            PeriodicKind::Annual => [1, 12],
            PeriodicKind::Stage => ($stage = FiscalYearStage::query()
                ->where('fiscal_year_id', $report->fiscal_year_id)->where('stage_no', $report->stage_no)->first())
                ? [$stage->start_month, $stage->end_month] : null,
            default => null,
        };
    }
}
