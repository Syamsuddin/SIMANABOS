<?php

use App\Actions\FiscalYears\CopyDocumentRequirements;
use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use Database\Seeders\DocumentTypeSeeder;

beforeEach(function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->admin = userWithRole(RoleName::AdminDinas, ['must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2035]);
    $this->bku = DocumentType::query()->where('code', 'BKU')->firstOrFail();
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
});

function postRequirement($test, array $overrides = [])
{
    return $test->actingAs($test->admin)->post(route('admin.fiscal-years.document-requirements.store', $test->year), array_merge([
        'document_type_id' => $test->bku->id, 'report_kind' => 'monthly', 'school_status' => 'all', 'is_required' => '1',
    ], $overrides));
}

it('DocumentRequirement: admin menambah aturan dan daftar menampilkannya', function () {
    postRequirement($this)->assertRedirect()->assertSessionHas('success');

    expect($this->year->documentRequirements()->count())->toBe(1);
    $this->actingAs($this->admin)->get(route('admin.fiscal-years.document-requirements.index', $this->year))
        ->assertOk()->assertSee('Buku Kas Umum (BKU)')->assertSee('Semua status');
});

it('DocumentRequirement: halaman kosong menampilkan state kosong', function () {
    $this->actingAs($this->admin)->get(route('admin.fiscal-years.document-requirements.index', $this->year))
        ->assertOk()->assertSee('Belum ada aturan dokumen');
});

it('DocumentRequirement: kombinasi ganda ditolak, termasuk sumber dana semua (NULL)', function () {
    postRequirement($this)->assertSessionDoesntHaveErrors();
    postRequirement($this)->assertSessionHasErrors('document_type_id');
    expect($this->year->documentRequirements()->count())->toBe(1);

    postRequirement($this, ['fund_source_id' => $this->fund->id])->assertSessionDoesntHaveErrors();
    expect($this->year->documentRequirements()->count())->toBe(2);
});

it('DocumentRequirement: nilai di luar daftar ditolak', function () {
    postRequirement($this, ['report_kind' => 'bulanan'])->assertSessionHasErrors('report_kind');
    postRequirement($this, ['school_status' => 'umum'])->assertSessionHasErrors('school_status');
    postRequirement($this, ['document_type_id' => 9999])->assertSessionHasErrors('document_type_id');
});

it('DocumentRequirement: hapus aturan dan salin dari tahun sebelumnya', function () {
    postRequirement($this);
    $requirement = $this->year->documentRequirements()->firstOrFail();
    $next = FiscalYear::factory()->create(['year' => 2036]);

    expect((new CopyDocumentRequirements)->handle($next))->toBe(1)
        ->and((new CopyDocumentRequirements)->handle($next))->toBe(0);

    $this->actingAs($this->admin)->delete(route('admin.fiscal-years.document-requirements.destroy', [$this->year, $requirement]))->assertRedirect();
    expect($this->year->documentRequirements()->count())->toBe(0);
});

it('DocumentRequirement: bukan admin ditolak dan tahun ditutup tidak bisa diubah', function () {
    $examiner = userWithRole(RoleName::PemeriksaDinas, ['must_change_password' => false]);
    $this->actingAs($examiner)->get(route('admin.fiscal-years.document-requirements.index', $this->year))->assertForbidden();

    $this->year->update(['status' => FiscalYearStatus::Closed]);
    postRequirement($this)->assertForbidden();
    expect($this->year->documentRequirements()->count())->toBe(0);
});
