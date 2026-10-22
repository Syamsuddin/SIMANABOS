<?php

namespace App\Services;

use App\Enums\RecapPeriod;
use App\Enums\ReportStatus;
use Brick\Math\BigDecimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap per sekolah untuk satu periode (docs/04 §Aturan agregasi antarperiode).
 * Arus dijumlahkan antarbulan; saldo awal = bulan pertama periode; saldo akhir = bulan terakhir periode,
 * tidak pernah dijumlahkan antarbulan. Hanya laporan berstatus ≥ diajukan yang dihitung.
 */
class RecapBuilder
{
    private const RECEIPT = 'coalesce(r.receipt_bos, 0) + coalesce(r.receipt_other, 0)';

    /**
     * @param  array{level?: ?string, district_id?: ?int, fund_source_id?: ?int}  $filters
     * @return Collection<int, array{npsn: string, school: string, level: string, district: string, reports: int, opening_fund: string, receipt: string, realization: string, treasury_deposit: string, ending_fund: string, ending_tax: string}>
     */
    public function bySchool(int $fiscalYearId, RecapPeriod $period, array $filters = []): Collection
    {
        [$from, $to] = $period->months();

        $flows = $this->base($fiscalYearId, $from, $to, $filters)
            ->selectRaw('s.id as id, count(*) as reports, coalesce(sum('.self::RECEIPT.'), 0) as receipt, coalesce(sum('.ReportAggregator::NET_REALIZATION.'), 0) as realization, coalesce(sum(r.treasury_deposit), 0) as treasury_deposit')
            ->groupBy('s.id')->get()->keyBy('id');

        $opening = $this->base($fiscalYearId, $from, $from, $filters)
            ->selectRaw('s.id as id, coalesce(sum(r.opening_fund), 0) as opening_fund')->groupBy('s.id')->pluck('opening_fund', 'id');

        $ending = $this->base($fiscalYearId, $to, $to, $filters)
            ->selectRaw('s.id as id, coalesce(sum(r.computed_ending_fund), 0) as ending_fund, coalesce(sum(r.computed_ending_tax), 0) as ending_tax')
            ->groupBy('s.id')->get()->keyBy('id');

        return DB::table('schools as s')
            ->join('districts as d', 'd.id', '=', 's.district_id')
            ->whereIn('s.id', $flows->keys())
            ->orderBy('d.name')->orderBy('s.name')
            ->get(['s.id', 's.npsn', 's.name', 's.level', 'd.name as district'])
            ->map(fn ($s) => [
                'npsn' => (string) $s->npsn,
                'school' => $s->name,
                'level' => (string) $s->level,
                'district' => $s->district,
                'reports' => (int) $flows[$s->id]->reports,
                'opening_fund' => $this->money($opening[$s->id] ?? 0),
                'receipt' => $this->money($flows[$s->id]->receipt),
                'realization' => $this->money($flows[$s->id]->realization),
                'treasury_deposit' => $this->money($flows[$s->id]->treasury_deposit),
                'ending_fund' => $this->money($ending[$s->id]->ending_fund ?? 0),
                'ending_tax' => $this->money($ending[$s->id]->ending_tax ?? 0),
            ])->values();
    }

    /**
     * Total kabupaten: arus dijumlahkan; saldo awal/akhir = Σ saldo per sekolah pada bulan pembatas periode.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{reports: int, opening_fund: string, receipt: string, realization: string, treasury_deposit: string, ending_fund: string, ending_tax: string}
     */
    public function totals(Collection $rows): array
    {
        $sum = fn (string $key): string => (string) $rows->reduce(
            fn (BigDecimal $carry, array $row) => $carry->plus($row[$key]),
            BigDecimal::zero(),
        )->toScale(2);

        return [
            'reports' => (int) $rows->sum('reports'),
            'opening_fund' => $sum('opening_fund'),
            'receipt' => $sum('receipt'),
            'realization' => $sum('realization'),
            'treasury_deposit' => $sum('treasury_deposit'),
            'ending_fund' => $sum('ending_fund'),
            'ending_tax' => $sum('ending_tax'),
        ];
    }

    /** @param  array{level?: ?string, district_id?: ?int, fund_source_id?: ?int}  $filters */
    private function base(int $fiscalYearId, int $fromMonth, int $toMonth, array $filters): Builder
    {
        return DB::table('monthly_reports as r')
            ->join('schools as s', 's.id', '=', 'r.school_id')
            ->where('r.fiscal_year_id', $fiscalYearId)
            ->where('r.status', '!=', ReportStatus::Draf->value)
            ->whereBetween('r.month', [$fromMonth, $toMonth])
            ->when($filters['level'] ?? null, fn (Builder $q, string $v) => $q->where('s.level', $v))
            ->when($filters['district_id'] ?? null, fn (Builder $q, int $v) => $q->where('s.district_id', $v))
            ->when($filters['fund_source_id'] ?? null, fn (Builder $q, int $v) => $q->where('r.fund_source_id', $v));
    }

    private function money(mixed $value): string
    {
        return (string) BigDecimal::of((string) $value)->toScale(2);
    }
}
