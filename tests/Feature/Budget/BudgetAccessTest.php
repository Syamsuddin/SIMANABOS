<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Budget\SaveBudgetVersion;
use App\Enums\RoleName;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\OpeningBalance;
use App\Models\School;

beforeEach(function () {
    $this->own = School::factory()->create();
    $this->other = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2031]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->type = $this->year->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang dan Jasa']);
    foreach ([$this->own, $this->other] as $school) {
        (new SaveAllocation)($school, $this->year, $this->fund, '100000000.00', 1);
    }
    (new SaveBudgetVersion)($this->other, $this->year, $this->fund, '2031-01-01', null, [$this->type->id => '1234567.00']);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->own->id, 'must_change_password' => false]);
});

it('operator tidak melihat RKAS sekolah lain', function () {
    $this->actingAs($this->operator)->get(route('school.budget.index'))
        ->assertOk()->assertDontSee('1.234.567,00');

    expect(BudgetVersion::query()->count())->toBe(0);
});

it('operator tidak dapat menyimpan saldo awal untuk sumber dana tanpa pagu miliknya', function () {
    $noAlloc = FundSource::query()->create(['code' => 'BOS_KIN', 'name' => 'BOS Kinerja']);

    $this->actingAs($this->operator)->put(route('school.opening-balance.update'), [
        'fiscal_year_id' => $this->year->id, 'fund_source_id' => $noAlloc->id,
        'fund_amount' => '1.000,00', 'tax_amount' => '0',
    ])->assertSessionHasErrors('fund_source_id');
    expect(OpeningBalance::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('hanya admin yang mengakses pagu', function (RoleName $role) {
    $user = userWithRole($role, ['must_change_password' => false, 'school_id' => $role->isSchoolRole() ? $this->own->id : null]);

    $this->actingAs($user)->get(route('admin.allocations.index'))->assertForbidden();
    $this->post(route('admin.allocations.store'), ['fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id, 'rows' => []])->assertForbidden();
})->with([RoleName::PemeriksaDinas, RoleName::PimpinanDinas, RoleName::OperatorSekolah, RoleName::KepalaSekolah]);

it('hanya operator yang mengisi RKAS', function (RoleName $role) {
    $user = userWithRole($role, ['must_change_password' => false, 'school_id' => $role->isSchoolRole() ? $this->own->id : null]);

    $this->actingAs($user)->post(route('school.budget.store'), [
        'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'effective_date' => '2031-01-01', 'lines' => [$this->type->id => '1.000,00'],
    ])->assertForbidden();
})->with([RoleName::AdminDinas, RoleName::PemeriksaDinas, RoleName::PimpinanDinas, RoleName::KepalaSekolah]);

it('akun dinas mendapat 404 pada halaman RKAS dan saldo awal sekolah', function () {
    $admin = userWithRole(RoleName::AdminDinas, ['must_change_password' => false]);

    $this->actingAs($admin)->get(route('school.budget.index'))->assertNotFound();
    $this->get(route('school.opening-balance.index'))->assertNotFound();
});

it('operator dan kepala sekolah tidak dapat memverifikasi saldo awal atau membuka antrean pemeriksa', function () {
    $balance = OpeningBalance::query()->withoutGlobalScopes()->create([
        'school_id' => $this->own->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'fund_amount' => '1.00', 'tax_amount' => '0.00', 'origin' => 'manual',
    ]);

    $this->actingAs($this->operator)->get(route('examiner.opening-balances.index'))->assertNotFound();
    $this->post(route('examiner.opening-balances.verify', $balance))->assertForbidden();
    expect($balance->fresh()->verified_at)->toBeNull();
});

it('sekolah lain tidak ditemukan saat operator mencoba memverifikasi saldo awal sekolah lain', function () {
    $balance = OpeningBalance::query()->withoutGlobalScopes()->create([
        'school_id' => $this->other->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'fund_amount' => '1.00', 'tax_amount' => '0.00', 'origin' => 'manual',
    ]);

    $this->actingAs($this->operator)->post(route('examiner.opening-balances.verify', $balance))->assertNotFound();
});

it('pimpinan dapat melihat antrean saldo awal tanpa tombol verifikasi', function () {
    $balance = OpeningBalance::query()->withoutGlobalScopes()->create([
        'school_id' => $this->own->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'fund_amount' => '1.00', 'tax_amount' => '0.00', 'origin' => 'manual',
    ]);
    $leader = userWithRole(RoleName::PimpinanDinas, ['must_change_password' => false]);

    $this->actingAs($leader)->get(route('examiner.opening-balances.index'))->assertOk()->assertSee($this->own->name)->assertDontSee('Verifikasi</button>', false);
    $this->post(route('examiner.opening-balances.verify', $balance))->assertForbidden();
});
