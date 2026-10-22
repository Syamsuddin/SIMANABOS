<?php

namespace App\Services;

use App\Enums\ReportStatus;
use Brick\Math\BigDecimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rumah tunggal agregasi dasbor kabupaten (docs/04 §Aturan agregasi antarperiode).
 * Arus (penerimaan, realisasi, penyetoran) dijumlahkan antarbulan; saldo diambil dari posisi bulan terakhir
 * periode dan TIDAK pernah dijumlahkan antarbulan. Hanya laporan berstatus ≥ diajukan yang dihitung.
 */
class ReportAggregator
{
    private const RECEIPT = 'coalesce(r.receipt_bos, 0) + coalesce(r.receipt_other, 0)';

    private const NET_REALIZATION = '(select coalesce(sum(l.gross_amount - l.refund_amount), 0) from report_expense_lines l where l.monthly_report_id = r.id)'
        .' + (select coalesce(sum(a.amount), 0) from report_adjustments a where a.monthly_report_id = r.id and a.kind = \'realisasi\')';

    /**
     * @param  array{level?: ?string, status?: ?string, district_id?: ?int, fund_source_id?: ?int}  $filters
     * @return array{
     *     counted: int, total: int, by_status: array<string, int>, late: int, rereview: int,
     *     receipt: string, realization: string, treasury_deposit: string, ending_fund: string, ending_tax: string,
     *     monthly: Collection<int, array{month: int, receipt: string, realization: string}>,
     *     by_district: Collection<int, array{name: string, receipt: string, realization: string, ending_fund: string, counted: int, total: int}>
     * }
     */
    public function summary(int $fiscalYearId, int $fromMonth, int $toMonth, array $filters = []): array
    {
        $filtered = fn (): Builder => $this->base($fiscalYearId, $fromMonth, $toMonth, $filters);
        $counted = fn (): Builder => $filtered()->where('r.status', '!=', ReportStatus::Draf->value);

        $byStatus = $filtered()->selectRaw('r.status as status, count(*) as n')->groupBy('r.status')->pluck('n', 'status')->map(fn ($n) => (int) $n)->all();

        $flows = $counted()->selectRaw('coalesce(sum('.self::RECEIPT.'), 0) as receipt, coalesce(sum('.self::NET_REALIZATION.'), 0) as realization, coalesce(sum(r.treasury_deposit), 0) as treasury_deposit')->first();

        $balance = $counted()->where('r.month', $toMonth)
            ->selectRaw('coalesce(sum(r.computed_ending_fund), 0) as ending_fund, coalesce(sum(r.computed_ending_tax), 0) as ending_tax')->first();

        $monthlyRows = $counted()->selectRaw('r.month as month, coalesce(sum('.self::RECEIPT.'), 0) as receipt, coalesce(sum('.self::NET_REALIZATION.'), 0) as realization')
            ->groupBy('r.month')->get()->keyBy('month');
        $monthly = collect(range($fromMonth, $toMonth))->map(fn (int $m) => [
            'month' => $m,
            'receipt' => $this->money($monthlyRows[$m]->receipt ?? 0),
            'realization' => $this->money($monthlyRows[$m]->realization ?? 0),
        ]);

        $districtFlows = $counted()->join('districts as d', 'd.id', '=', 's.district_id')
            ->selectRaw('d.id as id, d.name as name, coalesce(sum('.self::RECEIPT.'), 0) as receipt, coalesce(sum('.self::NET_REALIZATION.'), 0) as realization, count(*) as counted')
            ->groupBy('d.id', 'd.name')->get()->keyBy('id');
        $districtBalances = $counted()->where('r.month', $toMonth)->join('districts as d', 'd.id', '=', 's.district_id')
            ->selectRaw('d.id as id, coalesce(sum(r.computed_ending_fund), 0) as ending_fund')->groupBy('d.id')->pluck('ending_fund', 'id');
        $districtTotals = $filtered()->join('districts as d', 'd.id', '=', 's.district_id')
            ->selectRaw('d.id as id, d.name as name, count(*) as total')->groupBy('d.id', 'd.name')->orderBy('d.name')->get();

        return [
            'counted' => array_sum(array_diff_key($byStatus, [ReportStatus::Draf->value => 0])),
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'late' => (int) $filtered()->where('r.is_late', true)->count(),
            'rereview' => (int) $filtered()->where('r.needs_rereview', true)->count(),
            'receipt' => $this->money($flows->receipt),
            'realization' => $this->money($flows->realization),
            'treasury_deposit' => $this->money($flows->treasury_deposit),
            'ending_fund' => $this->money($balance->ending_fund),
            'ending_tax' => $this->money($balance->ending_tax),
            'monthly' => $monthly,
            'by_district' => $districtTotals->map(fn ($d) => [
                'name' => $d->name,
                'receipt' => $this->money($districtFlows[$d->id]->receipt ?? 0),
                'realization' => $this->money($districtFlows[$d->id]->realization ?? 0),
                'ending_fund' => $this->money($districtBalances[$d->id] ?? 0),
                'counted' => (int) ($districtFlows[$d->id]->counted ?? 0),
                'total' => (int) $d->total,
            ])->values(),
        ];
    }

    /** @param  array{level?: ?string, status?: ?string, district_id?: ?int, fund_source_id?: ?int}  $filters */
    private function base(int $fiscalYearId, int $fromMonth, int $toMonth, array $filters): Builder
    {
        return DB::table('monthly_reports as r')
            ->join('schools as s', 's.id', '=', 'r.school_id')
            ->where('r.fiscal_year_id', $fiscalYearId)
            ->whereBetween('r.month', [$fromMonth, $toMonth])
            ->when($filters['level'] ?? null, fn (Builder $q, string $v) => $q->where('s.level', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, string $v) => $q->where('r.status', $v))
            ->when($filters['district_id'] ?? null, fn (Builder $q, int $v) => $q->where('s.district_id', $v))
            ->when($filters['fund_source_id'] ?? null, fn (Builder $q, int $v) => $q->where('r.fund_source_id', $v));
    }

    private function money(mixed $value): string
    {
        return (string) BigDecimal::of((string) $value)->toScale(2);
    }
}
