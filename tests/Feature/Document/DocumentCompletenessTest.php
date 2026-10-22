<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Documents\UploadDocument;
use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use App\Services\DocumentCompletenessChecker;
use App\Services\ReportSubmissionValidator;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->seed(DocumentTypeSeeder::class);
    $this->school = School::factory()->create(['status' => 'negeri']);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2037]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
    $this->report = MonthlyReport::query()->where('fiscal_year_id', $this->year->id)->where('month', 1)->firstOrFail();
    $this->bku = DocumentType::query()->where('code', 'BKU')->firstOrFail();
    $this->bmd = DocumentType::query()->where('code', 'REKAP_BMD')->firstOrFail();
    $this->checker = app(DocumentCompletenessChecker::class);
});

function requireDoc($test, DocumentType $type, array $overrides = []): void
{
    $test->year->documentRequirements()->create(array_merge([
        'document_type_id' => $type->id, 'report_kind' => 'monthly', 'school_status' => 'all', 'is_required' => true,
    ], $overrides));
}

function attachDoc($test, array $types): void
{
    (new UploadDocument)($test->report, UploadedFile::fake()->createWithContent('x.pdf', "%PDF-1.4\n".uniqid()), $test->operator, array_map(fn ($t) => $t->id, $types));
}

it('Completeness: tanpa aturan, laporan dianggap lengkap', function () {
    expect($this->checker->forReport($this->report)['complete'])->toBeTrue();
});

it('Completeness: dokumen wajib yang belum diunggah dilaporkan kurang', function () {
    requireDoc($this, $this->bku);
    requireDoc($this, $this->bmd);

    $result = $this->checker->forReport($this->report);
    expect($result['complete'])->toBeFalse()
        ->and($result['missing']->pluck('code')->all())->toBe(['BKU', 'REKAP_BMD']);
});

it('Completeness: satu PDF bertanda BKU dan Rekap BMD memenuhi keduanya', function () {
    requireDoc($this, $this->bku);
    requireDoc($this, $this->bmd);
    attachDoc($this, [$this->bku, $this->bmd]);

    expect($this->checker->forReport($this->report)['complete'])->toBeTrue();
});

it('Completeness: aturan menghormati status sekolah, sumber dana, dan sifat wajib', function () {
    requireDoc($this, $this->bku, ['school_status' => 'swasta']);
    requireDoc($this, $this->bmd, ['fund_source_id' => FundSource::query()->create(['code' => 'KINERJA', 'name' => 'BOS Kinerja'])->id]);
    $optional = DocumentType::query()->where('code', 'SPTJM')->firstOrFail();
    requireDoc($this, $optional, ['is_required' => false]);

    expect($this->checker->forReport($this->report)['required'])->toHaveCount(0);

    requireDoc($this, DocumentType::query()->where('code', 'REALISASI')->firstOrFail(), ['school_status' => 'negeri', 'fund_source_id' => $this->fund->id]);
    expect($this->checker->forReport($this->report)['required']->pluck('code')->all())->toBe(['REALISASI']);
});

it('Completeness: dokumen yang dihapus tidak dihitung', function () {
    requireDoc($this, $this->bku);
    attachDoc($this, [$this->bku]);
    $this->report->documents()->firstOrFail()->delete();

    expect($this->checker->forReport($this->report->refresh())['complete'])->toBeFalse();
});

it('Completeness: ReportSubmissionValidator menolak pengajuan bila dokumen wajib kurang', function () {
    requireDoc($this, $this->bku);
    $errors = app(ReportSubmissionValidator::class)->errors($this->report);

    expect($errors)->toContain('Dokumen wajib belum diunggah: Buku Kas Umum (BKU).');

    attachDoc($this, [$this->bku]);
    expect(app(ReportSubmissionValidator::class)->errors($this->report->refresh()))->not->toContain('Dokumen wajib belum diunggah: Buku Kas Umum (BKU).');
});

it('Completeness: halaman laporan menampilkan kelengkapan dan empat state dokumen', function () {
    $this->actingAs($this->operator)->get(route('school.reports.edit', $this->report))
        ->assertOk()->assertSee('Belum ada dokumen')->assertSee('belum menetapkan dokumen wajib');

    requireDoc($this, $this->bku);
    $this->actingAs($this->operator)->get(route('school.reports.edit', $this->report))
        ->assertOk()->assertSee('Buku Kas Umum (BKU): belum ada');

    attachDoc($this, [$this->bku]);
    $this->actingAs($this->operator)->get(route('school.reports.edit', $this->report))
        ->assertOk()->assertSee('Buku Kas Umum (BKU): lengkap')->assertSee('x.pdf');
});
