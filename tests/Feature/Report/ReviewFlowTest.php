<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\SaveReportFigures;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2035]);
    $fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $fund, '100000000.00', 1);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);
    $this->examiner = userWithRole(RoleName::PemeriksaDinas);
    $report = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $this->year->id)->where('month', 1)->firstOrFail();
    $this->report = app(SaveReportFigures::class)($report, ['bank_balance_bku' => '0.00', 'cash_balance_bku' => '0.00'], [], $report->lock_version);
});

it('alur HTTP: operator ajukan, pemeriksa ambil, catat, minta perbaikan, operator tanggapi dan ajukan ulang, pemeriksa selesai', function () {
    $this->actingAs($this->operator)->post(route('school.reports.submit', $this->report))->assertRedirect();
    expect($this->report->refresh()->status)->toBe(ReportStatus::Diajukan);

    $this->actingAs($this->examiner)->get(route('review.reports.index'))->assertOk()->assertSee($this->school->name);
    $this->post(route('review.reports.claim', $this->report))->assertRedirect(route('review.reports.show', $this->report));
    $this->get(route('review.reports.show', $this->report))->assertOk()->assertSee('Belum ada catatan');

    $this->post(route('review.reports.notes.store', $this->report), ['target_type' => 'field', 'target_key' => 'receipt_bos', 'body' => 'Periksa angka.'])->assertRedirect();
    $this->post(route('review.reports.revise', $this->report))->assertRedirect(route('review.reports.index'));
    $note = $this->report->reviewNotes()->firstOrFail();

    $this->actingAs($this->operator)->get(route('school.reports.edit', $this->report))->assertOk()->assertSee('Periksa angka.');
    $this->put(route('school.reports.notes.respond', [$this->report, $note]), ['response_body' => 'Sudah dicek.'])->assertRedirect();
    $this->post(route('school.reports.submit', $this->report))->assertRedirect();

    $this->actingAs($this->examiner)->post(route('review.reports.claim', $this->report));
    $this->post(route('review.reports.notes.resolve', [$this->report, $note]))->assertRedirect();
    $this->post(route('review.reports.complete', $this->report))->assertRedirect();

    $this->get(route('review.reports.show', $this->report))->assertOk()->assertSee('bukan pernyataan keabsahan setiap transaksi');
    expect($this->report->refresh()->status)->toBe(ReportStatus::SelesaiDiperiksa);
});

it('guard domain gagal tampil sebagai pesan, bukan error server', function () {
    $other = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $this->year->id)->where('month', 2)->firstOrFail();

    $this->actingAs($this->operator)->from(route('school.reports.edit', $other))
        ->post(route('school.reports.submit', $other))
        ->assertRedirect(route('school.reports.edit', $other))
        ->assertSessionHasErrors([], null, 'domain');
});

it('klaim ulang lewat HTTP: laporan yang sudah diambil ditolak Policy untuk pemeriksa lain', function () {
    $this->actingAs($this->operator)->post(route('school.reports.submit', $this->report));
    $this->actingAs($this->examiner)->post(route('review.reports.claim', $this->report));

    $other = userWithRole(RoleName::PemeriksaDinas);
    $this->actingAs($other)->post(route('review.reports.claim', $this->report))->assertForbidden();
});

it('otorisasi: operator tidak bisa memeriksa, pemeriksa tidak bisa mengajukan, sekolah lain 404', function () {
    $this->actingAs($this->operator)->get(route('review.reports.index'))->assertNotFound();
    $this->actingAs($this->operator)->post(route('review.reports.claim', $this->report))->assertForbidden();
    $this->actingAs($this->examiner)->post(route('school.reports.submit', $this->report))->assertForbidden();

    $stranger = userWithRole(RoleName::OperatorSekolah, ['school_id' => School::factory()->create()->id]);
    $this->actingAs($stranger)->post(route('school.reports.submit', $this->report))->assertNotFound();
});

it('antrean kosong merender state kosong', function () {
    $this->actingAs($this->examiner)->get(route('review.reports.index'))->assertOk()->assertSee('Tidak ada laporan pada status ini');
});

it('pimpinan boleh membuka detail tetapi tanpa tombol aksi', function () {
    $this->actingAs($this->operator)->post(route('school.reports.submit', $this->report));
    $leader = userWithRole(RoleName::PimpinanDinas);

    $this->actingAs($leader)->get(route('review.reports.show', $this->report))->assertOk()->assertDontSee('Ambil untuk diperiksa');
});
