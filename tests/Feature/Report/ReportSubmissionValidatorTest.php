<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\SaveReportFigures;
use App\Enums\ReportStatus;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use App\Services\ReportSubmissionValidator;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2033]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->barjas = $this->year->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang dan Jasa']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
    $this->validator = app(ReportSubmissionValidator::class);
});

function save($test, int $month, array $figures = [], array $lines = []): MonthlyReport
{
    $report = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $test->year->id)->where('month', $month)->firstOrFail();
    $figures += ['bank_balance_bku' => '0.00', 'cash_balance_bku' => '0.00'];

    return app(SaveReportFigures::class)($report, $figures, $lines, $report->lock_version);
}

it('SubmissionValidator: laporan lengkap dan seimbang lulus', function () {
    $report = save($this, 1);

    expect($this->validator->errors($report))->toBe([]);
});

it('SubmissionValidator: neto kumulatif negatif ditolak menyebut nama jenis belanja', function () {
    $report = save($this, 1, [], [$this->barjas->id => ['gross_amount' => '0.00', 'refund_amount' => '10.00']]);

    $errors = $this->validator->errors($report);

    expect($errors)->toHaveCount(2) // negatif + selisih (kas hitung 10, BKU 0) tanpa catatan
        ->and(implode(' ', $errors))->toContain('Barang dan Jasa');
});

it('SubmissionValidator: selisih tidak nol tanpa catatan ditolak, dengan catatan diterima', function () {
    $report = save($this, 1, ['receipt_bos' => '100.00']);
    expect($this->validator->errors($report))->toBe(['Selisih saldo tidak nol; catatan selisih wajib diisi.']);

    $report = save($this, 1, ['receipt_bos' => '100.00', 'difference_note' => 'Menunggu rekonsiliasi bank']);
    expect($this->validator->errors($report))->toBe([]);
});

it('SubmissionValidator: saldo BKU kosong ditolak', function () {
    $report = MonthlyReport::query()->withoutGlobalScopes()->where('month', 1)->firstOrFail();

    expect($this->validator->errors($report))->toContain('Saldo bank dan saldo tunai menurut BKU wajib diisi.');
});

it('SubmissionValidator: penerimaan lain wajib berketerangan', function () {
    $report = save($this, 1, ['receipt_other' => '10.00', 'difference_note' => 'x']);

    expect($this->validator->errors($report))->toContain('Keterangan penerimaan lain wajib diisi bila penerimaan lain lebih dari nol.');
});

it('SubmissionValidator: bulan sebelumnya yang masih draf menghalangi', function () {
    $feb = save($this, 2);

    expect($this->validator->errors($feb))->toContain('Laporan bulan sebelumnya belum diajukan.');

    MonthlyReport::query()->withoutGlobalScopes()->where('month', 1)->firstOrFail()->forceFill(['status' => ReportStatus::Diajukan])->save();
    expect($this->validator->errors($feb->refresh()))->not->toContain('Laporan bulan sebelumnya belum diajukan.');
});
