<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\CompleteReview;
use App\Actions\Reports\SaveReportFigures;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2036]);
    $fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $fund, '100000000.00', 1);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);
    $this->examiner = userWithRole(RoleName::PemeriksaDinas);
    $this->admin = userWithRole(RoleName::AdminDinas);
});

function unlockReport($test, int $month): MonthlyReport
{
    return MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $test->year->id)->where('month', $month)->firstOrFail();
}

function unlockFinish($test, int $month): MonthlyReport
{
    $report = unlockReport($test, $month);
    $report = app(SaveReportFigures::class)($report, ['bank_balance_bku' => '0.00', 'cash_balance_bku' => '0.00'], [], $report->lock_version);
    $report = app(SubmitReport::class)($report, $test->operator);
    $report = app(StartReview::class)($report, $test->examiner);

    return app(CompleteReview::class)($report, $test->examiner);
}

it('Unlock: laporan selesai menolak simpan angka oleh operator (403)', function () {
    $report = unlockFinish($this, 1);

    $this->actingAs($this->operator)
        ->put(route('school.reports.update', $report), ['receipt_bos' => '1.000,00', 'lock_version' => $report->lock_version])
        ->assertForbidden();
});

it('Unlock: hanya admin dinas yang dapat membuka kunci; alasan wajib', function () {
    $report = unlockFinish($this, 1);

    $this->actingAs($this->examiner)->post(route('review.reports.unlock', $report), ['reason' => 'x'])->assertForbidden();
    $this->actingAs($this->operator)->post(route('review.reports.unlock', $report), ['reason' => 'x'])->assertForbidden();

    $this->actingAs($this->admin)->post(route('review.reports.unlock', $report), ['reason' => ''])->assertSessionHasErrors('reason');
    expect($report->refresh()->status)->toBe(ReportStatus::SelesaiDiperiksa);
});

it('Unlock: admin membuka kunci, laporan perlu perbaikan, alasan tercatat di riwayat', function () {
    $report = unlockFinish($this, 1);

    $this->actingAs($this->admin)->get(route('review.reports.show', $report))->assertOk()->assertSee('Buka kunci');
    $this->post(route('review.reports.unlock', $report), ['reason' => 'Salah input penerimaan.'])
        ->assertRedirect(route('review.reports.show', $report));

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::PerluPerbaikan)
        ->and($report->completed_at)->toBeNull()
        ->and($report->statusHistories()->get()->last()->note)->toBe('Salah input penerimaan.');
});

it('Unlock: angka diubah dan diajukan ulang menggeser bulan sesudahnya yang selesai (needs_rereview, status tetap)', function () {
    $jan = unlockFinish($this, 1);
    unlockFinish($this, 2);

    $this->actingAs($this->admin)->post(route('review.reports.unlock', $jan), ['reason' => 'Koreksi penerimaan.']);
    $jan = unlockReport($this, 1);
    app(SaveReportFigures::class)($jan, ['receipt_bos' => '1000.00', 'receipt_other' => '0.00', 'bank_balance_bku' => '0.00', 'cash_balance_bku' => '0.00'], [], $jan->lock_version);

    $feb = unlockReport($this, 2);
    expect($feb->status)->toBe(ReportStatus::SelesaiDiperiksa)->and($feb->needs_rereview)->toBeTrue();
});

it('Unlock: tahun ditutup menolak buka kunci', function () {
    $report = unlockFinish($this, 1);
    $this->year->forceFill(['status' => 'closed'])->save();

    $this->actingAs($this->admin)->post(route('review.reports.unlock', $report), ['reason' => 'Koreksi.'])->assertSessionHasErrorsIn('domain');
    expect($report->refresh()->status)->toBe(ReportStatus::SelesaiDiperiksa);
});
