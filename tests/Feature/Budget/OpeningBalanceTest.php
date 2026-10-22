<?php

use App\Actions\Budget\SaveAllocation;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\OpeningBalance;
use App\Models\School;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id, 'must_change_password' => false]);
    $this->examiner = userWithRole(RoleName::PemeriksaDinas, ['must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2031]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);
});

function putBalance($test, array $overrides = [])
{
    return $test->actingAs($test->operator)->put(route('school.opening-balance.update'), array_merge([
        'fiscal_year_id' => $test->year->id,
        'fund_source_id' => $test->fund->id,
        'fund_amount' => '5.000.000,00',
        'tax_amount' => '250.000,00',
    ], $overrides));
}

it('menampilkan status belum diisi', function () {
    $this->actingAs($this->operator)->get(route('school.opening-balance.index'))
        ->assertOk()->assertSee('Belum diisi');
});

it('operator menyimpan saldo awal manual yang belum terverifikasi', function () {
    putBalance($this)->assertRedirect()->assertSessionHas('success');

    $balance = OpeningBalance::query()->first();
    expect($balance->fund_amount)->toBe('5000000.00')->and($balance->tax_amount)->toBe('250000.00')
        ->and($balance->origin)->toBe('manual')->and($balance->isVerified())->toBeFalse()
        ->and($balance->blocksJanuaryCompletion())->toBeTrue();
});

it('pemeriksa memverifikasi sehingga Januari tidak lagi terhalang', function () {
    putBalance($this);
    $balance = OpeningBalance::query()->first();

    $this->actingAs($this->examiner)->get(route('examiner.opening-balances.index'))->assertOk()->assertSee($this->school->name);
    $this->post(route('examiner.opening-balances.verify', $balance))->assertRedirect()->assertSessionHas('success');

    $balance->refresh();
    expect($balance->isVerified())->toBeTrue()->and($balance->verified_by)->toBe($this->examiner->id)
        ->and($balance->blocksJanuaryCompletion())->toBeFalse();
});

it('mengunci angka setelah diverifikasi', function () {
    putBalance($this);
    $this->actingAs($this->examiner)->post(route('examiner.opening-balances.verify', OpeningBalance::query()->first()));

    putBalance($this, ['fund_amount' => '9.000.000,00'])->assertForbidden();
    expect(OpeningBalance::query()->value('fund_amount'))->toBe('5000000.00');
});

it('saldo awal carried_over tidak menghalangi Januari', function () {
    $balance = new OpeningBalance(['origin' => OpeningBalance::ORIGIN_CARRIED_OVER]);

    expect($balance->blocksJanuaryCompletion())->toBeFalse();
});

it('menolak angka tidak sah dan pajak kosong dianggap nol', function () {
    putBalance($this, ['fund_amount' => 'abc'])->assertSessionHasErrors('fund_amount');
    putBalance($this, ['tax_amount' => ''])->assertSessionDoesntHaveErrors();
    expect(OpeningBalance::query()->value('tax_amount'))->toBe('0.00');
});

it('pemeriksa tidak dapat memverifikasi dua kali', function () {
    putBalance($this);
    $balance = OpeningBalance::query()->first();
    $this->actingAs($this->examiner)->post(route('examiner.opening-balances.verify', $balance))->assertRedirect();

    $this->post(route('examiner.opening-balances.verify', $balance))->assertForbidden();
});
