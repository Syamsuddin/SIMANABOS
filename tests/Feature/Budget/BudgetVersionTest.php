<?php

use App\Actions\Budget\BudgetSummary;
use App\Actions\Budget\SaveAllocation;
use App\Enums\RoleName;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\School;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2031]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->barjas = $this->year->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang dan Jasa']);
    $this->modal = $this->year->expenseTypes()->create(['code' => 'MODAL', 'name' => 'Belanja Modal']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
});

function postVersion($test, array $overrides = [])
{
    return $test->actingAs($test->operator)->post(route('school.budget.store'), array_merge([
        'fiscal_year_id' => $test->year->id,
        'fund_source_id' => $test->fund->id,
        'effective_date' => '2031-01-01',
        'lines' => [$test->barjas->id => '60.000.000,00', $test->modal->id => '30.000.000,00'],
    ], $overrides));
}

it('menampilkan state kosong sebelum RKAS diisi', function () {
    $this->actingAs($this->operator)->get(route('school.budget.index'))
        ->assertOk()->assertSee('Belum ada RKAS')->assertSee('Isi RKAS versi 1');
});

it('menampilkan state kosong bila pagu belum ditetapkan', function () {
    $other = School::factory()->create();
    $op = userWithRole(RoleName::OperatorSekolah, ['school_id' => $other->id, 'must_change_password' => false]);

    $this->actingAs($op)->get(route('school.budget.index'))->assertOk()->assertSee('Pagu belum ditetapkan');
});

it('menyimpan versi 1 tanpa alasan dengan baris per jenis belanja', function () {
    postVersion($this)->assertRedirect()->assertSessionHas('success')->assertSessionMissing('warning');

    $version = BudgetVersion::query()->first();
    expect($version->version_no)->toBe(1)->and($version->reason)->toBeNull()
        ->and($version->lines()->count())->toBe(2)
        ->and(app(BudgetSummary::class)->total($version))->toBe('90000000.00');
});

it('membuat versi 2 yang mensyaratkan alasan dan tanggal setelah versi sebelumnya', function () {
    postVersion($this);

    postVersion($this, ['effective_date' => '2031-07-01'])->assertSessionHasErrors('reason');
    postVersion($this, ['effective_date' => '2031-01-01', 'reason' => 'Revisi'])->assertSessionHasErrors('effective_date');
    expect(BudgetVersion::query()->count())->toBe(1);

    postVersion($this, [
        'effective_date' => '2031-07-01', 'reason' => 'Revisi semester II',
        'lines' => [$this->barjas->id => '50.000.000,00', $this->modal->id => '40.000.000,00'],
    ])->assertSessionDoesntHaveErrors();

    expect(BudgetVersion::query()->orderBy('version_no')->pluck('version_no')->all())->toBe([1, 2]);
    // Versi 1 tidak berubah.
    expect(BudgetVersion::query()->where('version_no', 1)->first()->lines()->where('expense_type_id', $this->barjas->id)->value('amount'))->toBe('60000000.00');
});

it('memakai versi 1 untuk Juni dan versi 2 untuk September bila versi 2 berlaku 1 Juli', function () {
    postVersion($this);
    postVersion($this, ['effective_date' => '2031-07-01', 'reason' => 'Revisi']);
    $summary = app(BudgetSummary::class);

    expect($summary->versionForMonth($this->school, $this->year, $this->fund, 6)->version_no)->toBe(1)
        ->and($summary->versionForMonth($this->school, $this->year, $this->fund, 7)->version_no)->toBe(2)
        ->and($summary->versionForMonth($this->school, $this->year, $this->fund, 9)->version_no)->toBe(2);
});

it('tidak menentukan versi bagi bulan sebelum versi pertama berlaku', function () {
    postVersion($this, ['effective_date' => '2031-03-01']);

    expect(app(BudgetSummary::class)->versionForMonth($this->school, $this->year, $this->fund, 2))->toBeNull();
});

it('memperingatkan tanpa memblokir bila anggaran melebihi pagu ditambah saldo awal', function () {
    postVersion($this, ['lines' => [$this->barjas->id => '100.000.000,00', $this->modal->id => '1.000,00']])
        ->assertRedirect()->assertSessionHas('success')->assertSessionHas('warning');

    expect(BudgetVersion::query()->count())->toBe(1);
    $this->get(route('school.budget.index'))->assertSee('Anggaran melebihi pagu');
});

it('menghitung saldo awal dana dalam batas peringatan', function () {
    $this->actingAs($this->operator)->put(route('school.opening-balance.update'), [
        'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'fund_amount' => '10.000.000,00', 'tax_amount' => '0',
    ]);

    postVersion($this, ['lines' => [$this->barjas->id => '105.000.000,00', $this->modal->id => '0']])
        ->assertSessionMissing('warning');
});

it('menolak angka tidak sah, jenis belanja asing, dan sumber dana tanpa pagu', function () {
    postVersion($this, ['lines' => [$this->barjas->id => 'abc']])->assertSessionHasErrors('lines.'.$this->barjas->id);
    postVersion($this, ['lines' => [999999 => '1.000,00']])->assertSessionHasErrors('lines');
    postVersion($this, ['effective_date' => '2030-12-31'])->assertSessionHasErrors('effective_date');

    $noAlloc = FundSource::query()->create(['code' => 'BOS_KIN', 'name' => 'BOS Kinerja']);
    postVersion($this, ['fund_source_id' => $noAlloc->id])->assertSessionHasErrors('fund_source_id');
    expect(BudgetVersion::query()->count())->toBe(0);
});
