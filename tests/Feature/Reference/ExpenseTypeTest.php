<?php

use App\Enums\FiscalYearStatus;
use App\Enums\RoleName;
use App\Models\ExpenseType;
use App\Models\FiscalYear;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::AdminDinas, ['must_change_password' => false]);
    $this->year = FiscalYear::factory()->create(['year' => 2031]);
});

it('menampilkan state kosong jenis belanja', function () {
    $this->actingAs($this->admin)->get(route('admin.fiscal-years.expense-types.index', $this->year))
        ->assertOk()->assertSee('Belum ada jenis belanja');
});

it('membuat jenis belanja, kode unik per tahun dan tidak dapat diubah', function () {
    $url = route('admin.fiscal-years.expense-types.store', $this->year);
    $this->actingAs($this->admin)
        ->post($url, ['code' => 'BARJAS', 'name' => 'Barang dan Jasa', 'sort_order' => 1])
        ->assertRedirect(route('admin.fiscal-years.expense-types.index', $this->year))
        ->assertSessionHas('success');

    $this->post($url, ['code' => 'BARJAS', 'name' => 'Duplikat'])->assertSessionHasErrors('code');
    $this->post($url, ['code' => 'huruf kecil', 'name' => 'X'])->assertSessionHasErrors('code');

    $type = ExpenseType::firstWhere('code', 'BARJAS');
    $this->put(route('admin.fiscal-years.expense-types.update', [$this->year, $type]), [
        'code' => 'LAIN', 'name' => 'Nama Baru', 'is_active' => '0',
    ])->assertRedirect();

    $type->refresh();
    expect($type->code)->toBe('BARJAS')->and($type->name)->toBe('Nama Baru')->and($type->is_active)->toBeFalse();
});

it('mengizinkan kode yang sama di tahun anggaran lain', function () {
    $other = FiscalYear::factory()->create(['year' => 2032]);
    $other->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang']);

    $this->actingAs($this->admin)
        ->post(route('admin.fiscal-years.expense-types.store', $this->year), ['code' => 'BARJAS', 'name' => 'Barang'])
        ->assertSessionDoesntHaveErrors();
    expect(ExpenseType::where('code', 'BARJAS')->count())->toBe(2);
});

it('menyalin jenis belanja dari tahun sebelumnya tanpa menimpa yang ada', function () {
    $prev = FiscalYear::factory()->create(['year' => 2030]);
    $prev->expenseTypes()->create(['code' => 'A', 'name' => 'Satu', 'sort_order' => 1]);
    $prev->expenseTypes()->create(['code' => 'B', 'name' => 'Dua', 'sort_order' => 2, 'is_active' => false]);
    $this->year->expenseTypes()->create(['code' => 'A', 'name' => 'Sudah ada']);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.expense-types.copy', $this->year))
        ->assertRedirect()->assertSessionHas('success');

    expect($this->year->expenseTypes()->count())->toBe(2)
        ->and($this->year->expenseTypes()->where('code', 'A')->value('name'))->toBe('Sudah ada');

    $this->post(route('admin.fiscal-years.expense-types.copy', $this->year));
    expect($this->year->expenseTypes()->count())->toBe(2);
});

it('menolak perubahan pada tahun anggaran yang sudah ditutup', function () {
    $this->year->update(['status' => FiscalYearStatus::Closed]);
    $type = $this->year->expenseTypes()->create(['code' => 'A', 'name' => 'Satu']);

    $this->actingAs($this->admin)->get(route('admin.fiscal-years.expense-types.index', $this->year))->assertOk();
    $this->get(route('admin.fiscal-years.expense-types.create', $this->year))->assertForbidden();
    $this->post(route('admin.fiscal-years.expense-types.store', $this->year), ['code' => 'B', 'name' => 'Dua'])->assertForbidden();
    $this->put(route('admin.fiscal-years.expense-types.update', [$this->year, $type]), ['name' => 'Ubah'])->assertForbidden();
    $this->post(route('admin.fiscal-years.expense-types.copy', $this->year))->assertForbidden();
});

it('menolak peran selain admin dinas', function (RoleName $role) {
    $user = userWithRole($role, ['must_change_password' => false]);
    $type = $this->year->expenseTypes()->create(['code' => 'A', 'name' => 'Satu']);

    $this->actingAs($user)->get(route('admin.fiscal-years.expense-types.index', $this->year))->assertForbidden();
    $this->post(route('admin.fiscal-years.expense-types.store', $this->year), ['code' => 'B', 'name' => 'Dua'])->assertForbidden();
    $this->put(route('admin.fiscal-years.expense-types.update', [$this->year, $type]), ['name' => 'X'])->assertForbidden();
    expect(ExpenseType::count())->toBe(1);
})->with([RoleName::PemeriksaDinas, RoleName::PimpinanDinas, RoleName::OperatorSekolah, RoleName::KepalaSekolah]);

it('tidak menyelesaikan jenis belanja milik tahun lain lewat URL', function () {
    $other = FiscalYear::factory()->create(['year' => 2033]);
    $type = $other->expenseTypes()->create(['code' => 'A', 'name' => 'Satu']);

    $this->actingAs($this->admin)->get(route('admin.fiscal-years.expense-types.edit', [$this->year, $type]))->assertNotFound();
});
