<?php

namespace App\Actions\Budget;

use App\Enums\PeriodicKind;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\PeriodicReport;
use App\Models\School;

/** Menyimpan angka pembanding laporan periodik; satu baris per sekolah × tahun × sumber dana × jenis × tahap. */
class SavePeriodicReport
{
    public function __invoke(School $school, FiscalYear $fiscalYear, FundSource $fundSource, PeriodicKind $kind, int $stageNo, ?string $totalRealization, ?string $endingBalance, ?string $note): PeriodicReport
    {
        $report = PeriodicReport::query()->withoutGlobalScopes()->firstOrNew([
            'school_id' => $school->id, 'fiscal_year_id' => $fiscalYear->id, 'fund_source_id' => $fundSource->id,
            'kind' => $kind->value, 'stage_no' => $kind === PeriodicKind::Stage ? $stageNo : 0,
        ]);
        $report->fill([
            'reported_total_realization' => $kind->hasFigures() ? $totalRealization : null,
            'reported_ending_balance' => $kind->hasFigures() ? $endingBalance : null,
            'note' => $note,
        ])->save();

        return $report;
    }
}
