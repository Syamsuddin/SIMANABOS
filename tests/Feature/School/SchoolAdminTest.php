<?php

use App\Enums\RoleName;
use App\Models\District;
use App\Models\School;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::AdminDinas);
    $this->district = District::query()->create(['code' => 'KC01', 'name' => 'Kandangan']);
});

it('menampilkan state kosong daftar sekolah', function () {
    $this->actingAs($this->admin)->get(route('admin.schools.index'))->assertOk()->assertSee('Belum ada sekolah');
});

it('membuat sekolah dan NPSN harus 8 digit unik', function () {
    $payload = ['npsn' => '30300001', 'name' => 'SDN Contoh 1', 'level' => 'SD', 'status' => 'negeri', 'district_id' => $this->district->id];

    $this->actingAs($this->admin)->post(route('admin.schools.store'), $payload)->assertRedirect()->assertSessionHas('success');
    expect(School::where('npsn', '30300001')->exists())->toBeTrue();

    $this->actingAs($this->admin)->post(route('admin.schools.store'), $payload)->assertSessionHasErrors('npsn');
    $this->actingAs($this->admin)->post(route('admin.schools.store'), [...$payload, 'npsn' => '1234'])->assertSessionHasErrors('npsn');
    $this->actingAs($this->admin)->post(route('admin.schools.store'), [...$payload, 'npsn' => '30300002', 'level' => 'SMA'])->assertSessionHasErrors('level');
});

it('mengubah sekolah dan mencari berdasarkan nama atau NPSN', function () {
    $school = School::factory()->create(['district_id' => $this->district->id, 'name' => 'SDN Lama']);

    $this->actingAs($this->admin)
        ->put(route('admin.schools.update', $school), ['npsn' => $school->npsn, 'name' => 'SDN Baru', 'level' => 'SD', 'status' => 'negeri', 'district_id' => $this->district->id])
        ->assertRedirect(route('admin.schools.show', $school));
    expect($school->fresh()->name)->toBe('SDN Baru');

    $this->actingAs($this->admin)->get(route('admin.schools.index', ['q' => 'Baru']))->assertSee('SDN Baru');
    $this->actingAs($this->admin)->get(route('admin.schools.index', ['q' => 'tidak-ada']))->assertSee('Sekolah tidak ditemukan');
});

it('hanya menonaktifkan sekolah, tanpa route hapus', function () {
    $school = School::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.schools.toggle-active', $school))->assertRedirect();
    expect($school->fresh()->is_active)->toBeFalse()->and(School::withTrashed()->count())->toBe(1);

    $this->actingAs($this->admin)->post(route('admin.schools.toggle-active', $school));
    expect($school->fresh()->is_active)->toBeTrue();

    $this->actingAs($this->admin)->delete(route('admin.schools.show', $school))->assertStatus(405);
});

it('menolak peran selain admin mengelola sekolah', function (RoleName $role) {
    $user = userWithRole($role);
    $school = School::factory()->create();

    $this->actingAs($user)->get(route('admin.schools.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.schools.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.schools.toggle-active', $school))->assertForbidden();
    expect($school->fresh()->is_active)->toBeTrue();
})->with([RoleName::PemeriksaDinas, RoleName::PimpinanDinas]);
