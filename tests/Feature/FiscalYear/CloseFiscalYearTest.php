<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\CompleteReview;
use App\Actions\Reports\SaveReportFigures;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Actions\Reports\UnlockReport;
use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Exceptions\DomainRuleException;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\School;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->year = FiscalYear::factory()->create(['year' => 2040]);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);
    $this->examiner = userWithRole(RoleName::PemeriksaDinas);
    $this->admin = userWithRole(RoleName::AdminDinas);
});

function closeDecember($test, bool $finish): MonthlyReport
{
    $reports = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $test->year->id)->orderBy('month')->get()->keyBy('month');
    $december = [
        'receipt_bos' => '1000.00', 'tax_withheld' => '30.00', 'tax_remitted' => '10.00',
        'bank_balance_bku' => '1000.00', 'cash_balance_bku' => '20.00',
    ];

    foreach (range(1, 12) as $month) {
        $report = $reports[$month];
        $figures = $month === 12 ? $december : ['bank_balance_bku' => '0.00', 'cash_balance_bku' => '0.00'];
        $report = app(SaveReportFigures::class)($report, $figures, [], $report->lock_version);
        if ($month === 12 && ! $finish) {
            return $report;
        }
        $report = app(SubmitReport::class)($report, $test->operator);
        $report = app(StartReview::class)($report, $test->examiner);
        $report = app(CompleteReview::class)($report, $test->examiner);
    }

    return $report;
}

it('CloseFiscalYear: semua Desember selesai → saldo awal tahun berikut carried_over', function () {
    closeDecember($this, true);
    $next = FiscalYear::factory()->create(['year' => 2041]);

    $this->actingAs($this->admin)
        ->post(route('admin.fiscal-years.close', $this->year))
        ->assertRedirect(route('admin.fiscal-years.show', $this->year));

    $opening = OpeningBalance::query()->withoutGlobalScopes()->where('fiscal_year_id', $next->id)->sole();
    expect($opening->origin)->toBe(OpeningBalance::ORIGIN_CARRIED_OVER)
        ->and($opening->school_id)->toBe($this->school->id)
        ->and($opening->fund_amount)->toBe('1000.00')
        ->and($opening->tax_amount)->toBe('20.00');

    $this->year->refresh();
    expect($this->year->status)->toBe(FiscalYearStatus::Closed)
        ->and($this->year->closed_by)->toBe($this->admin->id)
        ->and($this->year->closed_at)->not->toBeNull();
});

it('CloseFiscalYear: Januari tahun berikut dihitung ulang dari saldo bawaan', function () {
    closeDecember($this, true);
    $next = FiscalYear::factory()->create(['year' => 2041]);
    (new SaveAllocation)($this->school, $next, $this->fund, '100000000.00', 1);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year));

    $january = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $next->id)->where('month', 1)->sole();
    expect($january->opening_fund)->toBe('1000.00')->and($january->opening_tax)->toBe('20.00');
});

it('CloseFiscalYear: Desember belum selesai → perlu ketik ulang tahun', function () {
    closeDecember($this, false);
    FiscalYear::factory()->create(['year' => 2041]);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year))->assertSessionHasErrors();
    expect($this->year->refresh()->status)->toBe(FiscalYearStatus::Open);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year), ['confirm_year' => '2039'])->assertSessionHasErrors();
    expect($this->year->refresh()->status)->toBe(FiscalYearStatus::Open);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year), ['confirm_year' => '2040'])->assertSessionDoesntHaveErrors();
    expect($this->year->refresh()->status)->toBe(FiscalYearStatus::Closed);
});

it('CloseFiscalYear: tahun berikut harus sudah dibuka', function () {
    closeDecember($this, true);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year))->assertSessionHasErrors();
    expect($this->year->refresh()->status)->toBe(FiscalYearStatus::Open)
        ->and(OpeningBalance::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('CloseFiscalYear: hanya admin dinas; tahun tertutup tidak dapat ditutup lagi', function () {
    closeDecember($this, true);
    FiscalYear::factory()->create(['year' => 2041]);

    $this->actingAs($this->examiner)->post(route('admin.fiscal-years.close', $this->year))->assertForbidden();
    $this->actingAs($this->operator)->post(route('admin.fiscal-years.close', $this->year))->assertForbidden();

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year));
    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year))->assertForbidden();
});

it('CloseFiscalYear: setelah ditutup, buka kunci laporan ditolak', function () {
    $december = closeDecember($this, true);
    FiscalYear::factory()->create(['year' => 2041]);
    $this->actingAs($this->admin)->post(route('admin.fiscal-years.close', $this->year));

    expect(fn () => app(UnlockReport::class)($december->refresh(), $this->admin, 'koreksi'))
        ->toThrow(DomainRuleException::class);
});

it('CloseFiscalYear: halaman tahun menampilkan tombol tutup hanya saat terbuka', function () {
    $this->actingAs($this->admin)->get(route('admin.fiscal-years.show', $this->year))->assertOk()->assertSee('Tutup tahun');

    $this->year->forceFill(['status' => FiscalYearStatus::Closed])->save();
    $this->actingAs($this->admin)->get(route('admin.fiscal-years.show', $this->year))->assertOk()->assertDontSee('Tutup tahun');
});
