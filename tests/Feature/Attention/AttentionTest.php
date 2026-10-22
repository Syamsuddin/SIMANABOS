<?php

use App\Enums\AttentionTrigger;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Enums\SchoolLevel;
use App\Models\Allocation;
use App\Models\BudgetLine;
use App\Models\BudgetVersion;
use App\Models\DocumentType;
use App\Models\ExpenseType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyDeadline;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\School;
use App\Models\StatusHistory;
use App\Services\AttentionListBuilder;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->year = FiscalYear::factory()->create(['year' => 2031]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->school = School::factory()->create();
    $this->builder = app(AttentionListBuilder::class);
});

afterEach(fn () => Carbon::setTestNow());

function attReport($test, int $month, ReportStatus $status, array $attrs = [], ?School $school = null): MonthlyReport
{
    $report = MonthlyReport::factory()->create([
        'school_id' => ($school ?? $test->school)->id, 'fiscal_year_id' => $test->year->id, 'fund_source_id' => $test->fund->id, 'month' => $month,
    ]);
    $report->forceFill(['status' => $status, ...$attrs])->save();

    return $report;
}

function attTriggers($test, array $filters = []): array
{
    return $test->builder->build($test->year, $filters)->map(fn ($r) => $r['trigger']->value)->all();
}

it('Attention: belum melapor = draf lewat tenggat', function () {
    MonthlyDeadline::query()->create(['fiscal_year_id' => $this->year->id, 'month' => 1, 'due_date' => '2031-02-10']);
    MonthlyDeadline::query()->create(['fiscal_year_id' => $this->year->id, 'month' => 2, 'due_date' => '2031-03-10']);
    attReport($this, 1, ReportStatus::Draf);
    attReport($this, 2, ReportStatus::Draf);

    Carbon::setTestNow('2031-02-20');

    $rows = $this->builder->build($this->year);
    expect($rows)->toHaveCount(1)->and($rows[0]['trigger'])->toBe(AttentionTrigger::BelumMelapor)->and($rows[0]['month'])->toBe(1);
});

it('Attention: dokumen kurang pada laporan diajukan', function () {
    $this->seed(DocumentTypeSeeder::class);
    $this->year->documentRequirements()->create([
        'document_type_id' => DocumentType::query()->where('code', 'BKU')->firstOrFail()->id,
        'report_kind' => 'monthly', 'school_status' => 'all', 'is_required' => true,
    ]);
    attReport($this, 1, ReportStatus::Diajukan);

    expect(attTriggers($this))->toBe(['dokumen_kurang']);
});

it('Attention: selisih saldo hanya pada laporan yang sudah diajukan', function () {
    attReport($this, 1, ReportStatus::Diajukan, ['computed_difference' => '100.00']);
    attReport($this, 2, ReportStatus::Draf, ['computed_difference' => '500.00']);
    attReport($this, 3, ReportStatus::Diajukan, ['computed_difference' => '0.00']);

    $rows = $this->builder->build($this->year);
    expect($rows)->toHaveCount(1)->and($rows[0]['trigger'])->toBe(AttentionTrigger::Selisih)->and($rows[0]['month'])->toBe(1);
});

it('Attention: perbaikan dan antrean lebih dari 14 hari', function () {
    Carbon::setTestNow('2031-03-31');
    $fix = attReport($this, 1, ReportStatus::PerluPerbaikan);
    StatusHistory::query()->create(['monthly_report_id' => $fix->id, 'from_status' => 'dalam_pemeriksaan', 'to_status' => 'perlu_perbaikan', 'actor_id' => userWithRole(RoleName::PemeriksaDinas)->id])
        ->forceFill(['created_at' => '2031-03-10'])->save();
    $recentFix = attReport($this, 2, ReportStatus::PerluPerbaikan);
    StatusHistory::query()->create(['monthly_report_id' => $recentFix->id, 'from_status' => 'dalam_pemeriksaan', 'to_status' => 'perlu_perbaikan', 'actor_id' => userWithRole(RoleName::PemeriksaDinas)->id])
        ->forceFill(['created_at' => '2031-03-25'])->save();
    attReport($this, 3, ReportStatus::Diajukan, ['submitted_at' => '2031-03-10 08:00:00']);
    attReport($this, 4, ReportStatus::Diajukan, ['submitted_at' => '2031-03-28 08:00:00']);

    $rows = $this->builder->build($this->year);
    expect($rows->pluck('trigger.value', 'month')->all())->toBe([1 => 'perbaikan_lama', 3 => 'antrean_lama']);
});

it('Attention: tinjau ulang', function () {
    attReport($this, 5, ReportStatus::SelesaiDiperiksa, ['needs_rereview' => true]);

    expect(attTriggers($this))->toBe(['tinjau_ulang']);
});

it('Attention: anggaran melebihi pagu + saldo awal, memakai versi RKAS terbaru', function () {
    Allocation::query()->create(['school_id' => $this->school->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id, 'amount' => '1000.00']);
    $type = ExpenseType::query()->create(['fiscal_year_id' => $this->year->id, 'code' => 'T1', 'name' => 'Jenis 1', 'sort_order' => 1]);
    $v1 = BudgetVersion::query()->create(['school_id' => $this->school->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id, 'version_no' => 1, 'effective_date' => '2031-01-01']);
    BudgetLine::query()->create(['budget_version_id' => $v1->id, 'expense_type_id' => $type->id, 'amount' => '5000.00']);
    expect(attTriggers($this))->toBe(['anggaran_melebihi_dana']);

    $v2 = BudgetVersion::query()->create(['school_id' => $this->school->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id, 'version_no' => 2, 'effective_date' => '2031-02-01']);
    BudgetLine::query()->create(['budget_version_id' => $v2->id, 'expense_type_id' => $type->id, 'amount' => '900.00']);
    expect(attTriggers($this))->toBe([]);
});

it('Attention: saldo awal manual belum diverifikasi; yang terverifikasi atau terbawa tidak muncul', function () {
    $other = School::factory()->create();
    $third = School::factory()->create();
    $make = fn (School $s, array $a) => OpeningBalance::query()->create(['school_id' => $s->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id, 'fund_amount' => '1.00', 'tax_amount' => '0.00', ...$a]);
    $make($this->school, ['origin' => 'manual']);
    $make($other, ['origin' => 'manual', 'verified_at' => now(), 'verified_by' => userWithRole(RoleName::PemeriksaDinas)->id]);
    $make($third, ['origin' => 'carried_over']);

    $rows = $this->builder->build($this->year);
    expect($rows)->toHaveCount(1)->and($rows[0]['school']->id)->toBe($this->school->id);
});

it('Attention: filter pemicu, jenjang, dan kecamatan', function () {
    $smp = School::factory()->create(['level' => SchoolLevel::SMP]);
    attReport($this, 1, ReportStatus::Diajukan, ['computed_difference' => '10.00']);
    attReport($this, 1, ReportStatus::SelesaiDiperiksa, ['needs_rereview' => true], $smp);

    expect($this->builder->build($this->year))->toHaveCount(2)
        ->and($this->builder->build($this->year, ['trigger' => 'selisih']))->toHaveCount(1)
        ->and($this->builder->build($this->year, ['level' => 'SMP'])->pluck('school.id')->all())->toBe([$smp->id])
        ->and($this->builder->build($this->year, ['district_id' => $this->school->district_id])->pluck('school.id')->all())->toBe([$this->school->id]);
});

it('Attention: halaman menampilkan badge pemicu; pimpinan boleh, role sekolah 404', function () {
    attReport($this, 1, ReportStatus::Diajukan, ['computed_difference' => '10.00']);

    $this->actingAs(userWithRole(RoleName::PimpinanDinas))->get(route('attention.index', ['tahun' => 2031]))
        ->assertOk()->assertSee('Selisih saldo')->assertSee($this->school->name);
    $this->actingAs(userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]))->get(route('attention.index'))->assertNotFound();
});

it('Attention: state kosong dan filter tidak valid', function () {
    $pemeriksa = userWithRole(RoleName::PemeriksaDinas);
    $this->actingAs($pemeriksa)->get(route('attention.index', ['tahun' => 2031]))->assertOk()->assertSee('Tidak ada sekolah yang perlu perhatian');
    $this->get(route('attention.index', ['pemicu' => 'ngawur']))->assertSessionHasErrors('pemicu');
});
