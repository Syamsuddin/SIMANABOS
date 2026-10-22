<?php

use App\Actions\FiscalYears\OpenFiscalYear;
use App\Enums\RoleName;
use App\Models\District;
use App\Models\FiscalYear;
use App\Models\FundSource;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::AdminDinas, ['must_change_password' => false]);
});

it('merender halaman referensi dengan state kosong untuk admin', function (string $routeName, string $text) {
    $this->actingAs($this->admin)->get(route($routeName))->assertOk()->assertSee($text);
})->with([
    'kecamatan' => ['admin.districts.index', 'Belum ada kecamatan'],
    'sumber dana' => ['admin.fund-sources.index', 'Belum ada sumber dana'],
    'tahun anggaran' => ['admin.fiscal-years.index', 'Belum ada tahun anggaran'],
]);

it('menolak peran selain admin dengan 403 dan tamu dengan redirect', function (RoleName $role) {
    $user = userWithRole($role, ['must_change_password' => false]);

    foreach (['admin.districts.index', 'admin.fund-sources.index', 'admin.fiscal-years.index', 'admin.fiscal-years.create'] as $name) {
        $this->actingAs($user)->get(route($name))->assertForbidden();
    }
    $this->actingAs($user)->post(route('admin.districts.store'), ['code' => 'X1', 'name' => 'X'])->assertForbidden();
    expect(District::count())->toBe(0);
})->with([RoleName::PemeriksaDinas, RoleName::PimpinanDinas, RoleName::OperatorSekolah, RoleName::KepalaSekolah]);

it('mengalihkan tamu ke login', function () {
    $this->get(route('admin.districts.index'))->assertRedirect(route('login'));
});

it('membuat dan mengubah kecamatan, kode harus unik', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.districts.store'), ['code' => 'KC01', 'name' => 'Kecamatan Contoh'])
        ->assertRedirect(route('admin.districts.index'))
        ->assertSessionHas('success');

    $district = District::firstWhere('code', 'KC01');
    $this->actingAs($this->admin)->get(route('admin.districts.index'))->assertSee('Kecamatan Contoh');

    $this->put(route('admin.districts.update', $district), ['code' => 'KC01', 'name' => 'Nama Baru'])->assertRedirect();
    expect($district->fresh()->name)->toBe('Nama Baru');

    $this->post(route('admin.districts.store'), ['code' => 'KC01', 'name' => 'Ganda'])->assertSessionHasErrors('code');
    $this->post(route('admin.districts.store'), ['code' => '', 'name' => ''])->assertSessionHasErrors(['code', 'name']);
});

it('membuat sumber dana, menolak ubah kode, dan boleh menonaktifkan', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.fund-sources.store'), ['code' => 'REGULER', 'name' => 'BOS Reguler', 'is_active' => '1'])
        ->assertRedirect(route('admin.fund-sources.index'));
    $this->post(route('admin.fund-sources.store'), ['code' => 'huruf kecil', 'name' => 'X'])->assertSessionHasErrors('code');

    $fund = FundSource::firstWhere('code', 'REGULER');
    $this->put(route('admin.fund-sources.update', $fund), ['code' => 'LAIN', 'name' => 'BOS Reguler 2', 'is_active' => '0'])
        ->assertRedirect();

    $fund->refresh();
    expect($fund->code)->toBe('REGULER')->and($fund->name)->toBe('BOS Reguler 2')->and($fund->is_active)->toBeFalse();
    $this->get(route('admin.fund-sources.index'))->assertSee('Nonaktif');
});

it('membuka tahun anggaran dengan 12 tenggat tanggal 10 bulan berikutnya', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.fiscal-years.store'), ['year' => 2026, 'default_deadline_day' => 10])
        ->assertRedirect();

    $year = FiscalYear::firstWhere('year', 2026);
    $dates = $year->deadlines->mapWithKeys(fn ($d) => [$d->month => $d->due_date->toDateString()]);

    expect($dates)->toHaveCount(12)
        ->and($dates[1])->toBe('2026-02-10')
        ->and($dates[11])->toBe('2026-12-10')
        ->and($dates[12])->toBe('2027-01-10');
    $this->get(route('admin.fiscal-years.show', $year))->assertOk()->assertSee('Belum ada tahap');
});

it('menolak tahun ganda dan tanggal tenggat di luar 1-28', function (array $input) {
    FiscalYear::factory()->create(['year' => 2026]);

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.store'), $input)->assertSessionHasErrors();
    expect(FiscalYear::count())->toBe(1);
})->with([
    'tahun ganda' => [['year' => 2026, 'default_deadline_day' => 10]],
    'tanggal 29' => [['year' => 2027, 'default_deadline_day' => 29]],
    'tanggal 0' => [['year' => 2027, 'default_deadline_day' => 0]],
]);

it('mengubah tenggat per bulan dan menolak format tanggal salah', function () {
    $year = app(OpenFiscalYear::class)->handle(2026, 10);
    $dates = $year->deadlines->pluck('due_date', 'month')->map->toDateString()->all();
    $dates[3] = '2026-04-15';

    $this->actingAs($this->admin)->put(route('admin.fiscal-years.deadlines.update', $year), ['due_dates' => $dates])
        ->assertSessionHasNoErrors();
    expect($year->deadlines()->where('month', 3)->first()->due_date->toDateString())->toBe('2026-04-15');

    $dates[3] = '15/04/2026';
    $this->put(route('admin.fiscal-years.deadlines.update', $year), ['due_dates' => $dates])
        ->assertSessionHasErrors('due_dates.3');
    expect($year->deadlines()->where('month', 3)->first()->due_date->toDateString())->toBe('2026-04-15');
});

it('mengelola tahap: tambah, tolak nomor ganda dan rentang terbalik, hapus', function () {
    $year = app(OpenFiscalYear::class)->handle(2026, 10);
    $stage = ['stage_no' => 1, 'label' => 'Tahap I', 'start_month' => 1, 'end_month' => 6];

    $this->actingAs($this->admin)->post(route('admin.fiscal-years.stages.store', $year), $stage)->assertSessionHasNoErrors();
    $this->post(route('admin.fiscal-years.stages.store', $year), $stage)->assertSessionHasErrors('stage_no');
    $this->post(route('admin.fiscal-years.stages.store', $year), [...$stage, 'stage_no' => 2, 'start_month' => 7, 'end_month' => 3])
        ->assertSessionHasErrors('end_month');
    $this->get(route('admin.fiscal-years.show', $year))->assertSee('Tahap I')->assertSee('Januari – Juni');

    $this->delete(route('admin.fiscal-years.stages.destroy', [$year, $year->stages()->first()]))->assertRedirect();
    expect($year->stages()->count())->toBe(0);
});

it('tidak membolehkan hapus tahap milik tahun lain', function () {
    $a = app(OpenFiscalYear::class)->handle(2026, 10, [['stage_no' => 1, 'label' => 'Tahap I', 'start_month' => 1, 'end_month' => 6]]);
    $b = app(OpenFiscalYear::class)->handle(2027, 10);

    $this->actingAs($this->admin)->delete(route('admin.fiscal-years.stages.destroy', [$b, $a->stages()->first()]))->assertNotFound();
    expect($a->stages()->count())->toBe(1);
});

it('mengunci jadwal pada tahun yang sudah ditutup', function () {
    $year = app(OpenFiscalYear::class)->handle(2025, 10);
    $year->update(['status' => 'closed']);
    $dates = $year->deadlines->pluck('due_date', 'month')->map->toDateString()->all();

    $this->actingAs($this->admin)->put(route('admin.fiscal-years.deadlines.update', $year), ['due_dates' => $dates])->assertForbidden();
    $this->post(route('admin.fiscal-years.stages.store', $year), ['stage_no' => 1, 'label' => 'T', 'start_month' => 1, 'end_month' => 2])->assertForbidden();
    $this->get(route('admin.fiscal-years.show', $year))->assertOk()->assertSee('sudah ditutup');
});
