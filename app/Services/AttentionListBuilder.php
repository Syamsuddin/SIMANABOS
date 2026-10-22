<?php

namespace App\Services;

use App\Actions\Budget\BudgetSummary;
use App\Enums\AttentionTrigger;
use App\Enums\ReportStatus;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\School;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Daftar sekolah bermasalah per pemicu (docs/01 F-11, docs/23). Satu baris per (pemicu, sekolah, laporan/sumber dana).
 * Ambang "lama" = 14 hari kalender sejak status berlaku.
 */
class AttentionListBuilder
{
    public const STALE_DAYS = 14;

    public function __construct(private readonly DocumentCompletenessChecker $completeness, private readonly BudgetSummary $budget) {}

    /**
     * @param  array{trigger?: ?string, level?: ?string, district_id?: ?int}  $filters
     * @return Collection<int, array{trigger: AttentionTrigger, school: School, fund_source: string, month: ?int, detail: string, report_id: ?int}>
     */
    public function build(FiscalYear $fiscalYear, array $filters = [], ?Carbon $today = null): Collection
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $only = ($filters['trigger'] ?? null) ? AttentionTrigger::tryFrom($filters['trigger']) : null;
        $wants = fn (AttentionTrigger $t) => $only === null || $only === $t;

        $rows = collect();
        $reports = fn (): Builder => MonthlyReport::query()->withoutGlobalScopes()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->with(['school', 'fundSource'])
            ->whereHas('school', fn (Builder $q) => $q
                ->when($filters['level'] ?? null, fn (Builder $q, string $v) => $q->where('level', $v))
                ->when($filters['district_id'] ?? null, fn (Builder $q, int $v) => $q->where('district_id', $v)));

        $add = function (AttentionTrigger $trigger, $school, string $fund, ?int $month, string $detail, ?int $reportId) use (&$rows): void {
            $rows->push(['trigger' => $trigger, 'school' => $school, 'fund_source' => $fund, 'month' => $month, 'detail' => $detail, 'report_id' => $reportId]);
        };

        if ($wants(AttentionTrigger::BelumMelapor)) {
            $due = DB::table('monthly_deadlines')->where('fiscal_year_id', $fiscalYear->id)->pluck('due_date', 'month');
            foreach ($reports()->where('status', ReportStatus::Draf)->get() as $r) {
                $dueDate = isset($due[$r->month]) ? Carbon::parse($due[$r->month])->startOfDay() : null;
                if ($dueDate !== null && $dueDate->lt($today)) {
                    $add(AttentionTrigger::BelumMelapor, $r->school, $r->fundSource->name, $r->month, 'Lewat tenggat '.(int) $dueDate->diffInDays($today).' hari ('.$dueDate->format('d-m-Y').').', $r->id);
                }
            }
        }

        if ($wants(AttentionTrigger::DokumenKurang)) {
            $open = [ReportStatus::Diajukan, ReportStatus::DalamPemeriksaan, ReportStatus::PerluPerbaikan];
            foreach ($reports()->whereIn('status', $open)->get() as $r) {
                $missing = $this->completeness->forReport($r)['missing'];
                if ($missing->isNotEmpty()) {
                    $add(AttentionTrigger::DokumenKurang, $r->school, $r->fundSource->name, $r->month, 'Kurang: '.$missing->pluck('name')->implode(', ').'.', $r->id);
                }
            }
        }

        if ($wants(AttentionTrigger::Selisih)) {
            foreach ($reports()->where('status', '!=', ReportStatus::Draf)->whereNotNull('computed_difference')->where('computed_difference', '!=', 0)->get() as $r) {
                $add(AttentionTrigger::Selisih, $r->school, $r->fundSource->name, $r->month, 'Selisih saldo kas Rp '.Money::format($r->computed_difference).'.', $r->id);
            }
        }

        if ($wants(AttentionTrigger::PerbaikanLama)) {
            foreach ($reports()->where('status', ReportStatus::PerluPerbaikan)->get() as $r) {
                $since = DB::table('status_histories')->where('monthly_report_id', $r->id)->where('to_status', ReportStatus::PerluPerbaikan->value)->max('created_at');
                if ($since !== null && Carbon::parse($since)->startOfDay()->lte($today->copy()->subDays(self::STALE_DAYS))) {
                    $add(AttentionTrigger::PerbaikanLama, $r->school, $r->fundSource->name, $r->month, 'Menunggu perbaikan sekolah '.(int) Carbon::parse($since)->startOfDay()->diffInDays($today).' hari.', $r->id);
                }
            }
        }

        if ($wants(AttentionTrigger::AntreanLama)) {
            foreach ($reports()->where('status', ReportStatus::Diajukan)->whereNotNull('submitted_at')->where('submitted_at', '<=', $today->copy()->subDays(self::STALE_DAYS))->get() as $r) {
                $add(AttentionTrigger::AntreanLama, $r->school, $r->fundSource->name, $r->month, 'Menunggu pemeriksa '.(int) $r->submitted_at->copy()->startOfDay()->diffInDays($today).' hari.', $r->id);
            }
        }

        if ($wants(AttentionTrigger::TinjauUlang)) {
            foreach ($reports()->where('needs_rereview', true)->get() as $r) {
                $add(AttentionTrigger::TinjauUlang, $r->school, $r->fundSource->name, $r->month, 'Bulan sebelumnya dibuka kunci; angka berubah.', $r->id);
            }
        }

        if ($wants(AttentionTrigger::AnggaranMelebihiDana)) {
            $latest = BudgetVersion::query()->withoutGlobalScopes()->where('fiscal_year_id', $fiscalYear->id)
                ->with(['school', 'fundSource'])
                ->whereHas('school', fn (Builder $q) => $q
                    ->when($filters['level'] ?? null, fn (Builder $q, string $v) => $q->where('level', $v))
                    ->when($filters['district_id'] ?? null, fn (Builder $q, int $v) => $q->where('district_id', $v)))
                ->orderByDesc('version_no')->get()
                ->unique(fn ($v) => $v->school_id.'-'.$v->fund_source_id);
            foreach ($latest as $version) {
                $warning = $this->budget->ceilingWarning($version);
                if ($warning !== null) {
                    $add(AttentionTrigger::AnggaranMelebihiDana, $version->school, $version->fundSource->name, null, 'RKAS Rp '.Money::format($warning['total']).' melebihi pagu + saldo awal Rp '.Money::format($warning['ceiling']).'.', null);
                }
            }
        }

        if ($wants(AttentionTrigger::SaldoAwalBelumDiverifikasi)) {
            $balances = OpeningBalance::query()->withoutGlobalScopes()->where('fiscal_year_id', $fiscalYear->id)
                ->where('origin', OpeningBalance::ORIGIN_MANUAL)->whereNull('verified_at')
                ->with(['school', 'fundSource'])
                ->whereHas('school', fn (Builder $q) => $q
                    ->when($filters['level'] ?? null, fn (Builder $q, string $v) => $q->where('level', $v))
                    ->when($filters['district_id'] ?? null, fn (Builder $q, int $v) => $q->where('district_id', $v)))
                ->get();
            foreach ($balances as $b) {
                $add(AttentionTrigger::SaldoAwalBelumDiverifikasi, $b->school, $b->fundSource->name, null, 'Saldo awal diisi sekolah dan belum diverifikasi pemeriksa.', null);
            }
        }

        return $rows->sortBy([
            fn ($a, $b) => strcmp($a['school']->name, $b['school']->name),
            fn ($a, $b) => ($a['month'] ?? 0) <=> ($b['month'] ?? 0),
        ])->values();
    }
}
