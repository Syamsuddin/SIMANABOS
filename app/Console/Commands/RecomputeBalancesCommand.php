<?php

namespace App\Console\Commands;

use App\Actions\Reports\SaveReportFigures;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Hitung ulang rantai saldo dan cache `computed_*` satu sekolah untuk satu tahun (docs/11, docs/25 langkah 9).
 * Rumus tetap di ReportBalanceCalculator lewat SaveReportFigures::recomputeFrom; status tidak disentuh.
 */
#[AsCommand(name: 'simanabos:recompute', description: 'Hitung ulang cache saldo laporan bulanan satu sekolah untuk satu tahun')]
class RecomputeBalancesCommand extends Command
{
    protected $signature = 'simanabos:recompute {npsn : NPSN sekolah} {year : Tahun anggaran}';

    public function handle(SaveReportFigures $figures): int
    {
        $school = School::query()->where('npsn', $this->argument('npsn'))->first();
        $fiscalYear = FiscalYear::query()->where('year', (int) $this->argument('year'))->first();

        if ($school === null || $fiscalYear === null) {
            $this->error($school === null ? 'Sekolah dengan NPSN tersebut tidak ditemukan.' : 'Tahun anggaran tidak ditemukan.');

            return self::FAILURE;
        }

        // Rantai dimulai dari bulan terkecil tiap sumber dana; recomputeFrom menjalar ke bulan sesudahnya.
        $starts = MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->orderBy('month')
            ->get()
            ->unique('fund_source_id');

        DB::transaction(fn () => $starts->each(fn (MonthlyReport $report) => $figures->recomputeFrom($report)));

        Log::info('Hitung ulang saldo', ['school_id' => $school->id, 'fiscal_year' => $fiscalYear->year, 'fund_sources' => $starts->count()]);
        $this->info("Saldo {$school->name} tahun {$fiscalYear->year} dihitung ulang untuk {$starts->count()} sumber dana.");

        return self::SUCCESS;
    }
}
