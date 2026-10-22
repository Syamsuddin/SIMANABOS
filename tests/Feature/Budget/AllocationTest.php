<?php

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\Allocation;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::AdminDinas, ['must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2031]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->school = School::factory()->create();
});

function postAllocation($test, array $rows)
{
    return $test->actingAs($test->admin)->post(route('admin.allocations.store'), [
        'fiscal_year_id' => $test->year->id,
        'fund_source_id' => $test->fund->id,
        'rows' => $rows,
    ]);
}

it('menampilkan state kosong bila belum ada sekolah aktif', function () {
    $this->school->forceDelete();

    $this->actingAs($this->admin)->get(route('admin.allocations.index'))
        ->assertOk()->assertSee('Belum ada sekolah aktif');
});

it('menyimpan pagu dan membuat 12 laporan draf bila aktif sejak Januari', function () {
    postAllocation($this, [$this->school->id => ['amount' => '120.000.000,00', 'active_from_month' => 1]])
        ->assertRedirect()->assertSessionHas('success');

    expect(Allocation::query()->value('amount'))->toBe('120000000.00');
    expect(MonthlyReport::query()->count())->toBe(12);
    expect(MonthlyReport::query()->pluck('status')->unique()->map->value->all())->toBe(['draf']);
});

it('membuat laporan draf Juli sampai Desember saja untuk active_from_month=7 dan idempoten', function () {
    $rows = [$this->school->id => ['amount' => '50.000.000,00', 'active_from_month' => 7]];

    postAllocation($this, $rows);
    postAllocation($this, $rows);

    expect(MonthlyReport::query()->orderBy('month')->pluck('month')->all())->toBe([7, 8, 9, 10, 11, 12]);
    expect(Allocation::query()->count())->toBe(1);
});

it('memperbarui pagu tanpa menimpa laporan yang sudah berjalan', function () {
    postAllocation($this, [$this->school->id => ['amount' => '10.000.000,00', 'active_from_month' => 1]]);
    $report = MonthlyReport::query()->where('month', 3)->first();
    $report->forceFill(['receipt_bos' => '5000.00'])->save();

    postAllocation($this, [$this->school->id => ['amount' => '20.000.000,00', 'active_from_month' => 1]]);

    expect(Allocation::query()->value('amount'))->toBe('20000000.00');
    expect(MonthlyReport::query()->count())->toBe(12);
    expect($report->fresh()->receipt_bos)->toBe('5000.00');
});

it('melewati baris tanpa pagu dan menolak angka tidak sah', function () {
    $other = School::factory()->create();

    postAllocation($this, [
        $this->school->id => ['amount' => '', 'active_from_month' => 1],
        $other->id => ['amount' => '1.000,00', 'active_from_month' => 1],
    ])->assertSessionDoesntHaveErrors();
    expect(Allocation::query()->count())->toBe(1);

    postAllocation($this, [$this->school->id => ['amount' => 'abc', 'active_from_month' => 1]])
        ->assertSessionHasErrors("rows.{$this->school->id}.amount");
    postAllocation($this, [$this->school->id => ['amount' => '10.000,00', 'active_from_month' => 13]])
        ->assertSessionHasErrors("rows.{$this->school->id}.active_from_month");
});

it('menolak pengisian pagu pada tahun anggaran yang sudah ditutup', function () {
    $this->year->update(['status' => FiscalYearStatus::Closed]);

    postAllocation($this, [$this->school->id => ['amount' => '1.000,00', 'active_from_month' => 1]])->assertForbidden();
    expect(Allocation::query()->count())->toBe(0);
});
