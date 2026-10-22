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
    $this->year = FiscalYear::factory()->create(['year' => 2034]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->schoolA = School::factory()->create();
    $this->schoolB = School::factory()->create();
    $this->opA = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->schoolA->id, 'must_change_password' => false]);
    $this->opB = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->schoolB->id, 'must_change_password' => false]);
    (new SaveAllocation)($this->schoolA, $this->year, $this->fund, '100000000.00', 1);
    (new SaveAllocation)($this->schoolB, $this->year, $this->fund, '100000000.00', 1);

    $this->reportA = MonthlyReport::query()->withoutGlobalScopes()->where('school_id', $this->schoolA->id)->where('month', 1)->firstOrFail();
    $bku = DocumentType::query()->where('code', 'BKU')->firstOrFail();
    $this->actingAs($this->opA)->post(route('school.reports.documents.store', $this->reportA), [
        'file' => UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nA\n%%EOF"), 'type_ids' => [$bku->id],
    ])->assertRedirect();
    $this->document = Document::query()->withoutGlobalScopes()->firstOrFail();
});

it('DocumentAccess: operator sekolah lain mendapat 404 untuk unduh, ganti, tandai, dan hapus', function () {
    $this->actingAs($this->opB)->get(route('documents.download', $this->document))->assertNotFound();
    $this->actingAs($this->opB)->get(route('school.reports.edit', $this->reportA))->assertNotFound();
    $this->actingAs($this->opB)->delete(route('school.reports.documents.destroy', [$this->reportA, $this->document]))->assertNotFound();
    $this->actingAs($this->opB)->post(route('school.reports.documents.store', $this->reportA), [])->assertNotFound();
});

it('DocumentAccess: dokumen milik laporan lain tidak bisa diakses lewat laporan sendiri', function () {
    $reportB = MonthlyReport::query()->withoutGlobalScopes()->where('school_id', $this->schoolB->id)->where('month', 1)->firstOrFail();

    $this->actingAs($this->opB)->delete(route('school.reports.documents.destroy', [$reportB, $this->document]))->assertNotFound();
});

it('DocumentAccess: admin, pemeriksa, dan pimpinan dapat mengunduh; kepala sekolah hanya sekolahnya', function () {
    foreach ([RoleName::AdminDinas, RoleName::PemeriksaDinas, RoleName::PimpinanDinas] as $role) {
        $this->actingAs(userWithRole($role, ['must_change_password' => false]))
            ->get(route('documents.download', $this->document))->assertOk();
    }

    $principalA = userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->schoolA->id, 'must_change_password' => false]);
    $principalB = userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->schoolB->id, 'must_change_password' => false]);
    $this->actingAs($principalA)->get(route('documents.download', $this->document))->assertOk();
    $this->actingAs($principalB)->get(route('documents.download', $this->document))->assertNotFound();
});

it('DocumentAccess: tamu diarahkan ke login dan non-operator tidak boleh mengubah dokumen', function () {
    auth()->logout();
    $this->get(route('documents.download', $this->document))->assertRedirect(route('login'));

    $principalA = userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->schoolA->id, 'must_change_password' => false]);
    $this->actingAs($principalA)->delete(route('school.reports.documents.destroy', [$this->reportA, $this->document]))->assertForbidden();
});

it('DocumentAccess: berkas tidak punya URL publik', function () {
    $path = $this->document->currentVersion->storage_path;
    expect(Storage::disk('public')->exists($path))->toBeFalse()
        ->and(config('filesystems.disks.private.root'))->not->toContain('public');
});
