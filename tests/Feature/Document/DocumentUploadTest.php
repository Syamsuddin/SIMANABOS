<?php

use App\Actions\Budget\SaveAllocation;
use App\Enums\RoleName;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->seed(DocumentTypeSeeder::class);
    $this->school = School::factory()->create();
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2033]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
    $this->report = MonthlyReport::query()->where('fiscal_year_id', $this->year->id)->where('month', 1)->firstOrFail();
    $this->bku = DocumentType::query()->where('code', 'BKU')->firstOrFail();
    $this->bmd = DocumentType::query()->where('code', 'REKAP_BMD')->firstOrFail();
});

function fakePdf(string $name = 'bku.pdf', string $body = 'isi-a'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n{$body}\n%%EOF");
}

function uploadDoc($test, ?UploadedFile $file = null, ?array $types = null, $report = null)
{
    return $test->actingAs($test->operator)->post(route('school.reports.documents.store', $report ?? $test->report), [
        'file' => $file ?? fakePdf(),
        'type_ids' => $types ?? [$test->bku->id],
    ]);
}

it('Document: unggah PDF tersimpan di disk private dengan nama UUID dan versi 1', function () {
    uploadDoc($this)->assertRedirect(route('school.reports.edit', $this->report))->assertSessionHas('success');

    $document = Document::query()->firstOrFail();
    $version = $document->currentVersion;
    expect($version->version_no)->toBe(1)
        ->and($version->original_name)->toBe('bku.pdf')
        ->and($version->storage_path)->toMatch('#^documents/2033/'.$this->school->npsn.'/[0-9a-f-]{36}\.pdf$#')
        ->and($version->sha256)->toHaveLength(64);
    Storage::disk('private')->assertExists($version->storage_path);
    expect($document->types->pluck('code')->all())->toBe(['BKU']);
});

it('Document: satu PDF ditandai BKU dan Rekap BMD menghitung keduanya', function () {
    uploadDoc($this, null, [$this->bku->id, $this->bmd->id]);

    expect(Document::query()->firstOrFail()->types->pluck('code')->sort()->values()->all())->toBe(['BKU', 'REKAP_BMD']);
});

it('Document: berkas .pdf berisi PNG ditolak', function () {
    // UploadedFile::fake() menebak MIME dari ekstensi, jadi pakai berkas nyata agar deteksi isi (finfo) teruji.
    $path = tempnam(sys_get_temp_dir(), 'png');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    $png = new UploadedFile($path, 'palsu.pdf', 'application/pdf', null, true);

    uploadDoc($this, $png)->assertSessionHasErrors('file');
    expect(Document::query()->count())->toBe(0);
});

it('Document: berkas melebihi batas ukuran atau kosong ditolak', function () {
    config(['simanabos.max_upload_kb' => 1]);
    $big = UploadedFile::fake()->createWithContent('besar.pdf', "%PDF-1.4\n".str_repeat('x', 3000));
    uploadDoc($this, $big)->assertSessionHasErrors('file');

    config(['simanabos.max_upload_kb' => 20480]);
    uploadDoc($this, UploadedFile::fake()->createWithContent('kosong.pdf', ''))->assertSessionHasErrors('file');
    expect(Document::query()->count())->toBe(0);
});

it('Document: jenis dokumen wajib ditandai dan harus jenis bulanan', function () {
    uploadDoc($this, null, [])->assertSessionHasErrors('type_ids');

    $rkas = DocumentType::query()->where('code', 'RKAS')->firstOrFail();
    uploadDoc($this, null, [$rkas->id])->assertSessionHasErrors('type_ids.0');
    expect(Document::query()->count())->toBe(0);
});

it('Document: ganti berkas membuat versi baru, versi lama tetap dapat diunduh', function () {
    uploadDoc($this);
    $document = Document::query()->firstOrFail();

    $this->actingAs($this->operator)->post(route('school.reports.documents.replace', [$this->report, $document]), [
        'file' => fakePdf('bku-revisi.pdf', 'isi-b'), 'replaced_reason' => 'Salah bulan',
    ])->assertRedirect()->assertSessionHas('success');

    $document->refresh();
    expect($document->versions)->toHaveCount(2)
        ->and($document->currentVersion->version_no)->toBe(2)
        ->and($document->currentVersion->replaced_reason)->toBe('Salah bulan');

    $this->actingAs($this->operator)->get(route('documents.download', [$document, 1]))->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($this->operator)->get(route('documents.download', $document))->assertOk();
});

it('Document: hash sama pada laporan lain sekolah sama menampilkan peringatan tanpa memblokir', function () {
    uploadDoc($this, fakePdf('a.pdf', 'sama'));
    $february = MonthlyReport::query()->where('fiscal_year_id', $this->year->id)->where('month', 2)->firstOrFail();

    uploadDoc($this, fakePdf('b.pdf', 'sama'), null, $february)
        ->assertRedirect()->assertSessionHas('success')->assertSessionHas('warning');
    expect(Document::query()->count())->toBe(2);
});

it('Document: hash sama pada laporan yang sama tidak memicu peringatan', function () {
    uploadDoc($this, fakePdf('a.pdf', 'sama'));
    uploadDoc($this, fakePdf('a.pdf', 'sama'))->assertSessionMissing('warning');
});

it('Document: tandai ulang jenis dokumen', function () {
    uploadDoc($this);
    $document = Document::query()->firstOrFail();

    $this->actingAs($this->operator)->put(route('school.reports.documents.tag', [$this->report, $document]), ['type_ids' => [$this->bmd->id]])
        ->assertRedirect()->assertSessionHas('success');

    expect($document->refresh()->types->pluck('code')->all())->toBe(['REKAP_BMD']);
});

it('Document: hapus sebelum pengajuan melakukan soft delete dan menjaga berkas', function () {
    uploadDoc($this);
    $document = Document::query()->firstOrFail();
    $path = $document->currentVersion->storage_path;

    $this->actingAs($this->operator)->delete(route('school.reports.documents.destroy', [$this->report, $document]))->assertRedirect();

    expect(Document::query()->count())->toBe(0)->and(Document::withTrashed()->count())->toBe(1);
    Storage::disk('private')->assertExists($path);
});

it('Document: hapus ditolak bila laporan pernah diajukan', function () {
    uploadDoc($this);
    $document = Document::query()->firstOrFail();
    MonthlyReport::query()->whereKey($this->report->id)->update(['submitted_at' => now()]);

    $this->actingAs($this->operator)->delete(route('school.reports.documents.destroy', [$this->report, $document]))->assertForbidden();
    expect(Document::query()->count())->toBe(1);
});

it('Document: unggah ditolak saat laporan tidak dapat diubah', function () {
    MonthlyReport::query()->whereKey($this->report->id)->update(['status' => 'diajukan']);

    uploadDoc($this)->assertForbidden();
    expect(Document::query()->count())->toBe(0);
});
