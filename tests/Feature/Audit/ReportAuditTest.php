<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\SaveReportFigures;
use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\ReportExpenseLine;
use App\Models\School;
use App\Services\ReportAuditTrail;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2034]);
    $fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $fund, '100000000.00', 1);
    $this->barjas = $this->year->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang dan Jasa']);
    $this->modal = $this->year->expenseTypes()->create(['code' => 'MODAL', 'name' => 'Belanja Modal']);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->report = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $this->year->id)->where('month', 1)->firstOrFail();
});

function auditSave($test, array $figures, array $lines = []): MonthlyReport
{
    $report = $test->report->fresh();

    return $test->report = app(SaveReportFigures::class)($report, $figures, $lines, $report->lock_version);
}

it('Audit: ubah receipt_bos tercatat dengan nilai lama, baru, pelaku, dan waktu', function () {
    $this->actingAs($this->operator);
    auditSave($this, ['receipt_bos' => '1000000.00']);
    auditSave($this, ['receipt_bos' => '2500000.00']);

    $log = Activity::query()->where('subject_type', MonthlyReport::class)->where('subject_id', $this->report->id)
        ->where('event', 'updated')->whereNotNull('attribute_changes->attributes->receipt_bos')->latest('id')->first();

    expect($log->causer_id)->toBe($this->operator->id)
        ->and($log->created_at)->not->toBeNull()
        ->and($log->attribute_changes['old']['receipt_bos'])->toBe('1000000.00')
        ->and($log->attribute_changes['attributes']['receipt_bos'])->toBe('2500000.00');
});

it('Audit: ubah dan hapus baris realisasi tercatat per baris', function () {
    $this->actingAs($this->operator);
    auditSave($this, [], [
        $this->barjas->id => ['gross_amount' => '500000.00', 'refund_amount' => '0.00'],
        $this->modal->id => ['gross_amount' => '300000.00', 'refund_amount' => '0.00'],
    ]);
    $barjasLineId = $this->report->lines()->where('expense_type_id', $this->barjas->id)->value('id');
    $modalLineId = $this->report->lines()->where('expense_type_id', $this->modal->id)->value('id');

    auditSave($this, [], [$this->barjas->id => ['gross_amount' => '750000.00', 'refund_amount' => '0.00']]);

    $updated = Activity::query()->where('subject_type', ReportExpenseLine::class)->where('subject_id', $barjasLineId)->where('event', 'updated')->first();
    expect($updated->attribute_changes['old']['gross_amount'])->toBe('500000.00')
        ->and($updated->attribute_changes['attributes']['gross_amount'])->toBe('750000.00')
        ->and(Activity::query()->where('subject_type', ReportExpenseLine::class)->where('subject_id', $modalLineId)->where('event', 'deleted')->exists())->toBeTrue()
        ->and($this->report->lines()->pluck('expense_type_id')->all())->toBe([$this->barjas->id]);
});

it('Audit: simpan tanpa perubahan tidak menambah entri baris', function () {
    $this->actingAs($this->operator);
    $lines = [$this->barjas->id => ['gross_amount' => '500000.00', 'refund_amount' => '0.00']];
    auditSave($this, [], $lines);
    $before = Activity::query()->where('subject_type', ReportExpenseLine::class)->count();

    auditSave($this, [], $lines);

    expect(Activity::query()->where('subject_type', ReportExpenseLine::class)->count())->toBe($before);
});

it('Audit: unggah versi dokumen tercatat dan masuk jejak laporan', function () {
    Storage::fake('private');
    $this->seed(DocumentTypeSeeder::class);
    $bku = DocumentType::query()->where('code', 'BKU')->firstOrFail();

    $this->actingAs($this->operator)->post(route('school.reports.documents.store', $this->report), [
        'file' => UploadedFile::fake()->createWithContent('bku.pdf', "%PDF-1.4\naudit\n%%EOF"),
        'type_ids' => [$bku->id],
    ])->assertRedirect();

    $version = DocumentVersion::query()->latest('id')->firstOrFail();
    expect(Activity::query()->where('subject_type', DocumentVersion::class)->where('subject_id', $version->id)->where('event', 'created')->value('causer_id'))
        ->toBe($this->operator->id);

    $trail = app(ReportAuditTrail::class)->forReport($this->report);
    expect($trail->pluck('subject')->all())->toContain('Versi dokumen #'.$version->id);
});

it('Audit: jejak laporan memuat perubahan angka berformat Rupiah dan pelaku', function () {
    $this->actingAs($this->operator);
    auditSave($this, ['receipt_bos' => '26512500.00']);

    $entry = app(ReportAuditTrail::class)->forReport($this->report)->firstWhere('subject', 'Laporan');
    expect($entry['actor'])->toBe($this->operator->name)
        ->and(collect($entry['changes'])->firstWhere('field', 'Penerimaan BOS')['new'])->toBe('26.512.500,00');
});

it('Audit: hak lihat jejak audit mengikuti docs/05', function () {
    $other = School::factory()->create();

    expect(userWithRole(RoleName::AdminDinas)->can('viewAudit', $this->report))->toBeTrue()
        ->and(userWithRole(RoleName::PemeriksaDinas)->can('viewAudit', $this->report))->toBeTrue()
        ->and(userWithRole(RoleName::PimpinanDinas)->can('viewAudit', $this->report))->toBeFalse()
        ->and($this->operator->can('viewAudit', $this->report))->toBeTrue()
        ->and(userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->school->id])->can('viewAudit', $this->report))->toBeTrue()
        ->and(userWithRole(RoleName::OperatorSekolah, ['school_id' => $other->id])->can('viewAudit', $this->report))->toBeFalse();
});

it('Audit: kartu jejak audit tampil untuk pemeriksa dan operator, tidak untuk pimpinan', function () {
    $this->actingAs($this->operator);
    auditSave($this, ['receipt_bos' => '1000000.00']);

    $this->actingAs(userWithRole(RoleName::PemeriksaDinas, ['must_change_password' => false]))
        ->get(route('review.reports.show', $this->report))->assertOk()->assertSee('Jejak audit')->assertSee('1.000.000,00');
    $this->actingAs(userWithRole(RoleName::PimpinanDinas, ['must_change_password' => false]))
        ->get(route('review.reports.show', $this->report))->assertOk()->assertDontSee('Jejak audit');
    $this->actingAs($this->operator)
        ->get(route('school.reports.edit', $this->report))->assertOk()->assertSee('Jejak audit');
});

it('Audit: laporan tanpa entri audit menampilkan state kosong', function () {
    // Data sebelum audit aktif (impor awal) tidak punya entri; hapus hanya di basis data uji.
    Activity::query()->delete();

    $this->actingAs(userWithRole(RoleName::AdminDinas, ['must_change_password' => false]))
        ->get(route('review.reports.show', MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $this->year->id)->where('month', 2)->firstOrFail()))
        ->assertOk()->assertSee('Belum ada jejak audit');
});
