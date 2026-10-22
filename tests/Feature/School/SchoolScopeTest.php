<?php

use App\Enums\RoleName;
use App\Models\School;

beforeEach(function () {
    $this->own = School::factory()->create();
    $this->other = School::factory()->create();
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->own->id]);
});

it('membatasi query School ke sekolah sendiri bagi role sekolah', function () {
    $this->actingAs($this->operator);

    expect(School::query()->pluck('id')->all())->toBe([$this->own->id]);
    expect(School::query()->find($this->other->id))->toBeNull();
});

it('menampilkan profil sekolah sendiri', function () {
    $this->actingAs($this->operator)->get(route('school.profile.show'))->assertOk()->assertSee($this->own->name)->assertSee('Belum ada pengelola');
});

it('mengembalikan 404 untuk sekolah lain di halaman admin dan 403 untuk sekolah sendiri', function () {
    $this->actingAs($this->operator)->get(route('admin.schools.show', $this->other))->assertNotFound();
    $this->actingAs($this->operator)->get(route('admin.schools.show', $this->own))->assertForbidden();
});

it('mengembalikan 404 pada profil bagi akun dinas', function () {
    $admin = userWithRole(RoleName::AdminDinas);

    $this->actingAs($admin)->get(route('school.profile.show'))->assertNotFound();
});

it('operator mengubah alamat dan telepon sekolahnya sendiri, kepala sekolah tidak', function () {
    $this->actingAs($this->operator)
        ->put(route('school.profile.update'), ['address' => 'Jl. Contoh 1', 'phone' => '0517-123'])
        ->assertRedirect(route('school.profile.show'));
    expect($this->own->fresh()->address)->toBe('Jl. Contoh 1')->and($this->other->fresh()->address)->toBeNull();

    $kepsek = userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->own->id]);
    $this->actingAs($kepsek)->get(route('school.profile.show'))->assertOk();
    $this->actingAs($kepsek)->put(route('school.profile.update'), ['address' => 'X'])->assertForbidden();
    expect($this->own->fresh()->address)->toBe('Jl. Contoh 1');
});

it('operator tidak dapat mengusulkan pengelola ke sekolah lain lewat rute admin', function () {
    $this->actingAs($this->operator)
        ->post(route('admin.schools.officials.store', $this->other), ['role' => 'bendahara', 'name' => 'X', 'started_at' => '2026-02-01'])
        ->assertNotFound();
});
