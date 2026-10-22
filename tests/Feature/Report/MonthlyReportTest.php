<?php

use App\Actions\Budget\SaveAllocation;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\School;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2032]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->barjas = $this->year->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang dan Jasa']);
    $this->modal = $this->year->expenseTypes()->create(['code' => 'MODAL', 'name' => 'Belanja Modal']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
    OpeningBalance::query()->create([
        'school_id' => $this->school->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'fund_amount' => '50000000.00', 'tax_amount' => '0.00', 'origin' => 'manual',
    ]);
});

function reportFor($test, int $month): MonthlyReport
{
    return MonthlyReport::query()->where('fiscal_year_id', $test->year->id)->where('month', $month)->firstOrFail();
}

function putReport($test, MonthlyReport $report, array $overrides = [], $as = null)
{
    return $test->actingAs($as ?? $test->operator)->put(route('school.reports.update', $report), array_merge([
        'lock_version' => $report->lock_version,
        'receipt_bos' => '0,00', 'receipt_other' => '12.500,00', 'receipt_other_note' => 'Bunga bank',
        'treasury_deposit' => '0,00', 'tax_withheld' => '1.200.000,00', 'tax_remitted' => '900.000,00',
        'bank_balance_bku' => '26.000.000,00', 'cash_balance_bku' => '812.500,00',
        'lines' => [
            $test->barjas->id => ['gross_amount' => '18.000.000,00', 'refund_amount' => '500.000,00'],
            $test->modal->id => ['gross_amount' => '6.000.000,00', 'refund_amount' => '0,00'],
        ],
    ], $overrides));
}

it('MonthlyReport: daftar menampilkan bulan dan status', function () {
    $this->actingAs($this->operator)->get(route('school.reports.index'))
        ->assertOk()->assertSee('BOS Reguler')->assertSee('Januari')->assertSee('Draf');
});

it('MonthlyReport: Februari tersimpan menghasilkan angka golden dan saldo awal berantai', function () {
    putReport($this, reportFor($this, 1), ['receipt_other' => '0,00', 'receipt_other_note' => '', 'tax_withheld' => '0,00', 'tax_remitted' => '0,00', 'lines' => [], 'bank_balance_bku' => '50.000.000,00', 'cash_balance_bku' => '0,00'])
        ->assertRedirect();

    putReport($this, reportFor($this, 2))->assertRedirect(route('school.reports.edit', reportFor($this, 2)))->assertSessionHas('success');

    $feb = reportFor($this, 2);
    expect($feb->opening_fund)->toBe('50000000.00')
        ->and($feb->computed_ending_fund)->toBe('26512500.00')
        ->and($feb->computed_ending_tax)->toBe('300000.00')
        ->and($feb->computed_difference)->toBe('0.00')
        ->and($feb->lines)->toHaveCount(2);
});

it('MonthlyReport: Maret membuka dengan saldo akhir Februari dan tidak bisa diedit', function () {
    putReport($this, reportFor($this, 1), ['receipt_other' => '0,00', 'receipt_other_note' => '', 'tax_withheld' => '0,00', 'tax_remitted' => '0,00', 'lines' => []]);
    putReport($this, reportFor($this, 2));

    $mar = reportFor($this, 3);
    expect($mar->opening_fund)->toBe('26512500.00')->and($mar->opening_tax)->toBe('300000.00');

    // Masukan opening_* dari klien diabaikan.
    putReport($this, $mar, ['opening_fund' => '999,00', 'lines' => []]);
    expect(reportFor($this, 3)->opening_fund)->toBe('26512500.00');

    $this->actingAs($this->operator)->get(route('school.reports.edit', $mar))
        ->assertOk()->assertSee('26.512.500,00')->assertSee('Sisa anggaran');
});

it('MonthlyReport: input 1.250.000,50 tersimpan 1250000.50', function () {
    putReport($this, reportFor($this, 1), ['receipt_bos' => '1.250.000,50']);

    expect(reportFor($this, 1)->receipt_bos)->toBe('1250000.50');
});

it('MonthlyReport: angka negatif atau tidak valid ditolak', function () {
    putReport($this, reportFor($this, 1), ['receipt_bos' => '-5,00'])->assertSessionHasErrors('receipt_bos');
    putReport($this, reportFor($this, 1), ['tax_withheld' => 'abc'])->assertSessionHasErrors('tax_withheld');
});

it('MonthlyReport: jenis belanja asing ditolak', function () {
    putReport($this, reportFor($this, 1), ['lines' => [99999 => ['gross_amount' => '1,00', 'refund_amount' => '0,00']]])
        ->assertSessionHasErrors('lines');
});

it('MonthlyReport: perubahan Januari menggeser saldo awal Februari dan menandai Februari selesai untuk ditinjau ulang', function () {
    putReport($this, reportFor($this, 2), ['lines' => []]);
    $feb = reportFor($this, 2);
    $feb->forceFill(['status' => ReportStatus::SelesaiDiperiksa])->save();

    putReport($this, reportFor($this, 1), ['receipt_bos' => '1.000,00', 'receipt_other' => '0,00', 'receipt_other_note' => '', 'lines' => []]);

    $feb = reportFor($this, 2);
    expect($feb->opening_fund)->toBe('50001000.00')
        ->and($feb->status)->toBe(ReportStatus::SelesaiDiperiksa)
        ->and($feb->needs_rereview)->toBeTrue();
});

it('MonthlyReport: laporan nihil menolkan penerimaan dan rincian', function () {
    putReport($this, reportFor($this, 1), ['is_nil' => '1', 'receipt_bos' => '5.000,00']);

    $jan = reportFor($this, 1);
    expect($jan->is_nil)->toBeTrue()->and($jan->receipt_bos)->toBe('0.00')->and($jan->lines)->toHaveCount(0);
});

it('MonthlyReport: lock_version usang ditolak tanpa mengubah data', function () {
    $jan = reportFor($this, 1);
    putReport($this, $jan, ['receipt_bos' => '1,00']);

    putReport($this, $jan, ['receipt_bos' => '9,00', 'lock_version' => $jan->lock_version])->assertSessionHasErrors('lock_version');
    expect(reportFor($this, 1)->receipt_bos)->toBe('1.00');
});

it('MonthlyReport: sekolah lain 404', function () {
    $other = School::factory()->create();
    $otherOp = userWithRole(RoleName::OperatorSekolah, ['school_id' => $other->id, 'must_change_password' => false]);
    $jan = reportFor($this, 1);

    $this->actingAs($otherOp)->get(route('school.reports.edit', $jan))->assertNotFound();
    putReport($this, $jan, [], $otherOp)->assertNotFound();
});

it('MonthlyReport: status selain draf/perlu perbaikan tidak bisa diubah', function () {
    $jan = reportFor($this, 1);
    $jan->forceFill(['status' => ReportStatus::Diajukan])->save();

    putReport($this, $jan->refresh())->assertForbidden();
    $this->actingAs($this->operator)->get(route('school.reports.edit', $jan))->assertOk()->assertSee('hanya dapat dilihat');
});

it('MonthlyReport: perlu perbaikan masih bisa diubah operator', function () {
    $jan = reportFor($this, 1);
    $jan->forceFill(['status' => ReportStatus::PerluPerbaikan])->save();

    putReport($this, $jan->refresh(), ['receipt_bos' => '2,00'])->assertRedirect();
    expect(reportFor($this, 1)->receipt_bos)->toBe('2.00');
});

it('MonthlyReport: kepala sekolah dan peran dinas tidak bisa mengubah angka', function () {
    $head = userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $admin = userWithRole(RoleName::AdminDinas, ['must_change_password' => false]);

    putReport($this, reportFor($this, 1), [], $head)->assertForbidden();
    putReport($this, reportFor($this, 1), [], $admin)->assertForbidden();
});

it('MonthlyReport: perubahan angka tercatat di audit dan status tidak berubah', function () {
    putReport($this, reportFor($this, 1), ['receipt_bos' => '3,00']);

    expect(reportFor($this, 1)->status)->toBe(ReportStatus::Draf)
        ->and(Activity::query()->where('subject_type', MonthlyReport::class)->exists())->toBeTrue();
});

it('MonthlyReport: tamu dialihkan ke login', function () {
    $this->get(route('school.reports.index'))->assertRedirect(route('login'));
});
