<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\SaveReportFigures;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2035]);
    $fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $fund, '100000000.00', 1);
});

function recomputeMonth($test, int $month): MonthlyReport
{
    return MonthlyReport::query()->withoutGlobalScopes()->where('school_id', $test->school->id)
        ->where('fiscal_year_id', $test->year->id)->where('month', $month)->firstOrFail();
}

it('menghitung ulang cache saldo yang rusak tanpa mengubah status', function () {
    $january = recomputeMonth($this, 1);
    app(SaveReportFigures::class)($january, ['receipt_bos' => '5000000.00'], [], $january->lock_version);

    // Rusak cache secara langsung (meniru data lama/insiden); computed_* bukan sumber kebenaran.
    MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $this->year->id)
        ->update(['computed_ending_fund' => '1.00', 'opening_fund' => '9.00']);

    $this->artisan('simanabos:recompute', ['npsn' => $this->school->npsn, 'year' => 2035])->assertSuccessful();

    expect(recomputeMonth($this, 1)->computed_ending_fund)->toBe('5000000.00')
        ->and(recomputeMonth($this, 2)->opening_fund)->toBe('5000000.00')
        ->and(recomputeMonth($this, 12)->computed_ending_fund)->toBe('5000000.00')
        ->and(recomputeMonth($this, 12)->status->value)->toBe('draf');
});

it('gagal untuk NPSN atau tahun yang tidak ada', function () {
    $this->artisan('simanabos:recompute', ['npsn' => '00000000', 'year' => 2035])->assertFailed();
    $this->artisan('simanabos:recompute', ['npsn' => $this->school->npsn, 'year' => 1999])->assertFailed();
});
