<?php

use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Enums\SchoolLevel;
use App\Models\ExpenseType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use App\Services\ReportAggregator;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->year = FiscalYear::factory()->create(['year' => 2036]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
});

function dashReport($test, School $school, int $month, ReportStatus $status, array $attrs = []): MonthlyReport
{
    $report = MonthlyReport::factory()->create([
        'school_id' => $school->id, 'fiscal_year_id' => $test->year->id, 'fund_source_id' => $test->fund->id, 'month' => $month,
    ]);
    $report->forceFill(['status' => $status, ...$attrs])->save();

    return $report;
}

it('Dashboard: saldo kabupaten = Σ saldo akhir bulan terakhir periode, bukan jumlah antarbulan', function () {
    $a = School::factory()->create();
    $b = School::factory()->create();
    dashReport($this, $a, 8, ReportStatus::Diajukan, ['computed_ending_fund' => '1000.00', 'computed_ending_tax' => '10.00']);
    dashReport($this, $b, 8, ReportStatus::SelesaiDiperiksa, ['computed_ending_fund' => '2000.00', 'computed_ending_tax' => '20.00']);
    dashReport($this, $a, 9, ReportStatus::Diajukan, ['computed_ending_fund' => '1500.00', 'computed_ending_tax' => '15.00']);
    dashReport($this, $b, 9, ReportStatus::Diajukan, ['computed_ending_fund' => '2500.00', 'computed_ending_tax' => '25.00']);

    $summary = app(ReportAggregator::class)->summary($this->year->id, 1, 9);

    expect($summary['ending_fund'])->toBe('4000.00')->and($summary['ending_tax'])->toBe('40.00');
});

it('Dashboard: draf tidak dihitung dan label "X dari Y" benar', function () {
    $a = School::factory()->create();
    $b = School::factory()->create();
    dashReport($this, $a, 1, ReportStatus::Diajukan, ['receipt_bos' => '500.00', 'receipt_other' => '50.00']);
    dashReport($this, $b, 1, ReportStatus::Draf, ['receipt_bos' => '9999.00']);

    $summary = app(ReportAggregator::class)->summary($this->year->id, 1, 12);

    expect($summary['receipt'])->toBe('550.00')->and($summary['counted'])->toBe(1)->and($summary['total'])->toBe(2);

    $admin = userWithRole(RoleName::AdminDinas);
    $this->actingAs($admin)->get(route('dashboard.index', ['tahun' => 2036]))->assertOk()->assertSee('1 dari 2 laporan');
});

it('Dashboard: realisasi neto = bruto − pengembalian + penyesuaian realisasi', function () {
    $school = School::factory()->create();
    $report = dashReport($this, $school, 2, ReportStatus::Diajukan);
    $type = ExpenseType::query()->create(['fiscal_year_id' => $this->year->id, 'code' => 'T1', 'name' => 'Jenis 1', 'sort_order' => 1]);
    DB::table('report_expense_lines')->insert(['monthly_report_id' => $report->id, 'expense_type_id' => $type->id, 'gross_amount' => '1000.00', 'refund_amount' => '100.00', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('report_adjustments')->insert(['monthly_report_id' => $report->id, 'kind' => 'realisasi', 'expense_type_id' => $type->id, 'origin_fiscal_year_id' => $this->year->id, 'origin_month' => 1, 'amount' => '50.00', 'reason' => 'koreksi', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('report_adjustments')->insert(['monthly_report_id' => $report->id, 'kind' => 'penerimaan', 'expense_type_id' => null, 'origin_fiscal_year_id' => $this->year->id, 'origin_month' => 1, 'amount' => '777.00', 'reason' => 'bukan realisasi', 'created_at' => now(), 'updated_at' => now()]);

    expect(app(ReportAggregator::class)->summary($this->year->id, 1, 12)['realization'])->toBe('950.00');
});

it('Dashboard: filter jenjang, kecamatan, status, dan bulan mengubah semua angka secara konsisten', function () {
    $sd = School::factory()->create(['level' => SchoolLevel::SD]);
    $smp = School::factory()->create(['level' => SchoolLevel::SMP]);
    dashReport($this, $sd, 3, ReportStatus::Diajukan, ['receipt_bos' => '100.00']);
    dashReport($this, $smp, 3, ReportStatus::SelesaiDiperiksa, ['receipt_bos' => '200.00']);
    dashReport($this, $smp, 4, ReportStatus::Diajukan, ['receipt_bos' => '400.00']);
    $aggregator = app(ReportAggregator::class);

    expect($aggregator->summary($this->year->id, 1, 12)['receipt'])->toBe('700.00')
        ->and($aggregator->summary($this->year->id, 1, 12, ['level' => 'SMP'])['receipt'])->toBe('600.00')
        ->and($aggregator->summary($this->year->id, 1, 12, ['district_id' => $sd->district_id])['receipt'])->toBe('100.00')
        ->and($aggregator->summary($this->year->id, 1, 12, ['status' => 'selesai_diperiksa'])['receipt'])->toBe('200.00')
        ->and($aggregator->summary($this->year->id, 4, 4)['receipt'])->toBe('400.00')
        ->and($aggregator->summary($this->year->id, 1, 12, ['level' => 'SMP'])['total'])->toBe(2);
});

it('Dashboard: bendera terlambat dan tinjau ulang terhitung', function () {
    $school = School::factory()->create();
    dashReport($this, $school, 1, ReportStatus::Draf, ['is_late' => true]);
    dashReport($this, $school, 2, ReportStatus::SelesaiDiperiksa, ['needs_rereview' => true]);

    $summary = app(ReportAggregator::class)->summary($this->year->id, 1, 12);

    expect($summary['late'])->toBe(1)->and($summary['rereview'])->toBe(1);
});

it('Dashboard: pimpinan, pemeriksa, admin boleh membuka; role sekolah 404; tamu diarahkan login', function () {
    foreach ([RoleName::PimpinanDinas, RoleName::PemeriksaDinas, RoleName::AdminDinas] as $role) {
        $this->actingAs(userWithRole($role))->get(route('dashboard.index'))->assertOk();
    }
    foreach ([RoleName::OperatorSekolah, RoleName::KepalaSekolah] as $role) {
        $this->actingAs(userWithRole($role, ['school_id' => School::factory()->create()->id]))->get(route('dashboard.index'))->assertNotFound();
    }
    auth()->logout();
    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
});

it('Dashboard: state kosong tanpa laporan dan tanpa tahun anggaran', function () {
    $pimpinan = userWithRole(RoleName::PimpinanDinas);
    $this->actingAs($pimpinan)->get(route('dashboard.index', ['tahun' => 2036]))->assertOk()->assertSee('Tidak ada laporan pada filter ini');

    FiscalYear::query()->delete();
    $this->get(route('dashboard.index'))->assertOk()->assertSee('Belum ada tahun anggaran');
});

it('Dashboard: filter tidak valid ditolak', function () {
    $this->actingAs(userWithRole(RoleName::PimpinanDinas))
        ->get(route('dashboard.index', ['dari' => 9, 'sampai' => 3, 'jenjang' => 'SMA']))
        ->assertSessionHasErrors(['sampai', 'jenjang']);
});

it('Dashboard: tabel alternatif grafik tampil', function () {
    $school = School::factory()->create();
    dashReport($this, $school, 1, ReportStatus::Diajukan, ['receipt_bos' => '1000.00']);

    $this->actingAs(userWithRole(RoleName::PimpinanDinas))->get(route('dashboard.index', ['tahun' => 2036]))
        ->assertOk()->assertSee('Lihat sebagai tabel')->assertSee('1.000,00');
});
