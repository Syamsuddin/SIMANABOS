<?php

use App\Actions\Budget\SaveAllocation;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\Document;
use App\Models\ExpenseType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\PeriodicReport;
use App\Models\School;
use App\Services\PeriodicComparator;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->seed(DocumentTypeSeeder::class);
    $this->school = School::factory()->create();
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2041]);
    $this->year->stages()->create(['stage_no' => 1, 'start_month' => 1, 'end_month' => 6, 'label' => 'Tahap I']);
    $this->year->stages()->create(['stage_no' => 2, 'start_month' => 7, 'end_month' => 12, 'label' => 'Tahap II']);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
    $this->expense = ExpenseType::query()->create(['fiscal_year_id' => $this->year->id, 'code' => 'B1', 'name' => 'Belanja Uji']);
});

function periodicMonth($test, int $month, ReportStatus $status, string $gross, string $refund = '0.00'): void
{
    $report = MonthlyReport::query()->where('fiscal_year_id', $test->year->id)->where('month', $month)->firstOrFail();
    $report->lines()->create(['expense_type_id' => $test->expense->id, 'gross_amount' => $gross, 'refund_amount' => $refund]);
    $report->forceFill(['status' => $status])->save();
}

function savePeriodic($test, array $overrides = [])
{
    return $test->actingAs($test->operator)->put(route('school.periodic.update'), [
        'fiscal_year_id' => $test->year->id, 'fund_source_id' => $test->fund->id,
        'kind' => 'stage', 'stage_no' => 1, 'reported_total_realization' => '1.000,00',
        ...$overrides,
    ]);
}

it('Periodic: selisih Tahap I = dilaporkan dikurangi Σ realisasi neto Jan–Jun (draf dan di luar rentang tidak dihitung)', function () {
    periodicMonth($this, 1, ReportStatus::Diajukan, '600.00', '100.00');
    periodicMonth($this, 6, ReportStatus::SelesaiDiperiksa, '300.00');
    periodicMonth($this, 3, ReportStatus::Draf, '9999.00');
    periodicMonth($this, 7, ReportStatus::Diajukan, '5000.00');

    savePeriodic($this)->assertRedirect()->assertSessionHas('success');

    $report = PeriodicReport::query()->firstOrFail();
    $result = app(PeriodicComparator::class)->compare($report);

    expect($result['monthly_total'])->toBe('800.00')
        ->and($result['counted'])->toBe(2)
        ->and($result['reported'])->toBe('1000.00')
        ->and($result['difference'])->toBe('200.00');

    $this->get(route('school.periodic.index', ['tahun' => 2041]))->assertOk()->assertSee('200,00');
});

it('Periodic: menyimpan angka periodik tidak mengubah laporan bulanan', function () {
    periodicMonth($this, 1, ReportStatus::Diajukan, '600.00');
    $before = MonthlyReport::query()->where('month', 1)->firstOrFail()->only(['computed_ending_fund', 'receipt_bos', 'status']);

    savePeriodic($this);

    expect(MonthlyReport::query()->where('month', 1)->firstOrFail()->only(['computed_ending_fund', 'receipt_bos', 'status']))->toEqual($before);
});

it('Periodic: simpan dua kali memperbarui satu baris, bukan menggandakan', function () {
    savePeriodic($this);
    savePeriodic($this, ['reported_total_realization' => '2.000,00']);

    expect(PeriodicReport::query()->count())->toBe(1)
        ->and(PeriodicReport::query()->first()->reported_total_realization)->toBe('2000.00');
});

it('Periodic: tahap yang belum ditetapkan Dinas ditolak', function () {
    savePeriodic($this, ['stage_no' => 3])->assertSessionHasErrors('stage_no');

    expect(PeriodicReport::query()->count())->toBe(0);
});

it('Periodic: angka rupiah tidak sah ditolak', function () {
    savePeriodic($this, ['reported_total_realization' => 'abc'])->assertSessionHasErrors('reported_total_realization');
});

it('Periodic: sumber dana tanpa pagu sekolah ditolak', function () {
    $other = FundSource::query()->create(['code' => 'LAIN', 'name' => 'Lain']);

    savePeriodic($this, ['fund_source_id' => $other->id])->assertSessionHasErrors('fund_source_id');
});

it('Periodic: tahun tertutup tidak bisa diisi', function () {
    $this->year->forceFill(['status' => 'closed'])->save();

    savePeriodic($this)->assertForbidden();
});

it('Periodic: peran non-operator tidak bisa mengisi', function () {
    $examiner = userWithRole(RoleName::PemeriksaDinas, ['must_change_password' => false]);

    $this->actingAs($examiner)->put(route('school.periodic.update'), [
        'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id, 'kind' => 'stage', 'stage_no' => 1,
    ])->assertForbidden();
});

it('Periodic: unggah PDF tersimpan privat, ditandai TAHAP otomatis, dan bisa diganti dengan versi lama tetap ada', function () {
    savePeriodic($this);
    $report = PeriodicReport::query()->firstOrFail();
    $pdf = fn (string $body) => UploadedFile::fake()->createWithContent('tahap.pdf', "%PDF-1.4\n{$body}\n%%EOF");

    $this->post(route('school.periodic.documents.store', $report), ['file' => $pdf('a')])->assertRedirect()->assertSessionHas('success');
    $document = Document::query()->firstOrFail();
    expect($document->types->pluck('code')->all())->toBe(['TAHAP']);
    Storage::disk('private')->assertExists($document->currentVersion->storage_path);

    $this->post(route('school.periodic.documents.replace', [$report, $document]), ['file' => $pdf('b')])->assertRedirect();
    expect($document->refresh()->versions)->toHaveCount(2)->and($document->currentVersion->version_no)->toBe(2);
});

it('Periodic: berkas non-PDF ditolak', function () {
    savePeriodic($this);
    $report = PeriodicReport::query()->firstOrFail();

    // Berkas nyata agar deteksi isi (finfo) teruji; fake() menebak MIME dari ekstensi.
    $path = tempnam(sys_get_temp_dir(), 'png');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    $png = new UploadedFile($path, 'palsu.pdf', 'application/pdf', null, true);

    $this->post(route('school.periodic.documents.store', $report), ['file' => $png])->assertSessionHasErrors('file');
    expect(Document::query()->count())->toBe(0);
});

it('Periodic: sekolah lain tidak bisa mengunggah ke laporan periodik sekolah ini (404) dan tidak mengunduh dokumennya', function () {
    savePeriodic($this);
    $report = PeriodicReport::query()->firstOrFail();
    $this->post(route('school.periodic.documents.store', $report), ['file' => UploadedFile::fake()->createWithContent('t.pdf', "%PDF-1.4\nz\n%%EOF")]);
    $document = Document::query()->firstOrFail();

    $otherSchool = School::factory()->create();
    $intruder = userWithRole(RoleName::OperatorSekolah, ['school_id' => $otherSchool->id, 'must_change_password' => false]);

    $this->actingAs($intruder)->post(route('school.periodic.documents.store', $report), ['file' => UploadedFile::fake()->createWithContent('t.pdf', "%PDF-1.4\nq\n%%EOF")])->assertNotFound();
    $this->actingAs($intruder)->get(route('documents.download', $document))->assertNotFound();
});

it('Periodic: dinas melihat daftar lintas sekolah dengan selisih, sekolah lain tidak terlihat oleh operator', function () {
    periodicMonth($this, 1, ReportStatus::Diajukan, '600.00');
    savePeriodic($this, ['reported_total_realization' => '600,00']);
    $viewer = userWithRole(RoleName::PimpinanDinas, ['must_change_password' => false]);

    $this->actingAs($viewer)->get(route('periodic.index', ['tahun' => 2041]))->assertOk()->assertSee($this->school->name)->assertSee('Sesuai');

    $otherSchool = School::factory()->create();
    $other = userWithRole(RoleName::OperatorSekolah, ['school_id' => $otherSchool->id, 'must_change_password' => false]);
    expect(PeriodicReport::query()->count())->toBe(1);
    $this->actingAs($other);
    expect(PeriodicReport::query()->count())->toBe(0);
});

it('Periodic: berita acara tidak punya perbandingan angka', function () {
    savePeriodic($this, ['kind' => 'minutes', 'stage_no' => null, 'reported_total_realization' => '5.000,00'])->assertRedirect();

    $report = PeriodicReport::query()->firstOrFail();
    expect($report->reported_total_realization)->toBeNull()
        ->and(app(PeriodicComparator::class)->compare($report))->toBeNull();
});
