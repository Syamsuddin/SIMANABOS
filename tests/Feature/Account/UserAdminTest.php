<?php

use App\Enums\RoleName;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::AdminDinas);
    $this->school = School::factory()->create();
});

it('membuat akun operator terikat sekolah dan wajib ganti sandi', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'username' => 'op.sdn1', 'name' => 'Operator SDN 1', 'role' => 'operator_sekolah',
        'school_id' => $this->school->id, 'password' => 'Sandi12345',
    ])->assertRedirect(route('admin.users.index'));

    $user = User::where('username', 'op.sdn1')->first();
    expect($user->hasRole('operator_sekolah'))->toBeTrue()
        ->and($user->school_id)->toBe($this->school->id)
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check('Sandi12345', $user->password))->toBeTrue();
});

it('role sekolah wajib sekolah dan role dinas dilarang sekolah', function () {
    $base = ['username' => 'user1', 'name' => 'U', 'password' => 'Sandi12345'];

    $this->actingAs($this->admin)->post(route('admin.users.store'), [...$base, 'role' => 'operator_sekolah'])->assertSessionHasErrors('school_id');
    $this->actingAs($this->admin)->post(route('admin.users.store'), [...$base, 'role' => 'pemeriksa_dinas', 'school_id' => $this->school->id])->assertSessionHasErrors('school_id');
    $this->actingAs($this->admin)->post(route('admin.users.store'), [...$base, 'role' => 'pemeriksa_dinas'])->assertSessionDoesntHaveErrors();
    expect(User::where('username', 'user1')->first()->school_id)->toBeNull();
});

it('menolak sandi lemah dan username ganda', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), ['username' => 'a1', 'name' => 'A', 'role' => 'pemeriksa_dinas', 'password' => 'pendek'])->assertSessionHasErrors('password');
    $this->actingAs($this->admin)->post(route('admin.users.store'), ['username' => $this->admin->username, 'name' => 'A', 'role' => 'pemeriksa_dinas', 'password' => 'Sandi12345'])->assertSessionHasErrors('username');
});

it('mengubah akun tanpa mengubah username, dan menonaktifkan', function () {
    $target = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);

    $this->actingAs($this->admin)->put(route('admin.users.update', $target), [
        'name' => 'Nama Baru', 'role' => 'kepala_sekolah', 'school_id' => $this->school->id, 'is_active' => 0,
    ])->assertRedirect(route('admin.users.index'));

    $target->refresh();
    expect($target->name)->toBe('Nama Baru')->and($target->is_active)->toBeFalse()->and($target->hasRole('kepala_sekolah'))->toBeTrue()
        ->and($target->hasRole('operator_sekolah'))->toBeFalse();

    $this->actingAs($this->admin)->put(route('admin.users.update', $target), [
        'username' => 'diganti', 'name' => 'X', 'role' => 'kepala_sekolah', 'school_id' => $this->school->id, 'is_active' => 1,
    ])->assertSessionHasErrors('username');
});

it('admin tidak dapat menonaktifkan akunnya sendiri', function () {
    $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
        'name' => $this->admin->name, 'role' => 'admin_dinas', 'is_active' => 0,
    ])->assertSessionHasErrors('is_active');
    expect($this->admin->fresh()->is_active)->toBeTrue();
});

it('mengatur ulang sandi dan memaksa ganti sandi', function () {
    $target = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);

    $this->actingAs($this->admin)->put(route('admin.users.password.reset', $target), ['password' => 'SandiBaru123'])->assertRedirect();

    $target->refresh();
    expect(Hash::check('SandiBaru123', $target->password))->toBeTrue()->and($target->must_change_password)->toBeTrue();
});

it('menolak peran selain admin mengelola akun', function (RoleName $role) {
    $user = userWithRole($role, $role->isSchoolRole() ? ['school_id' => $this->school->id] : []);
    $target = userWithRole(RoleName::PemeriksaDinas);

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.store'), ['username' => 'z1', 'name' => 'Z', 'role' => 'admin_dinas', 'password' => 'Sandi12345'])->assertForbidden();
    $this->actingAs($user)->put(route('admin.users.password.reset', $target), ['password' => 'SandiBaru123'])->assertForbidden();
    expect(User::where('username', 'z1')->exists())->toBeFalse();
})->with([RoleName::PemeriksaDinas, RoleName::PimpinanDinas, RoleName::OperatorSekolah, RoleName::KepalaSekolah]);

it('merender daftar dan formulir akun', function () {
    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSee($this->admin->username);
    $this->actingAs($this->admin)->get(route('admin.users.create'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.users.edit', $this->admin))->assertOk()->assertSee('Atur ulang sandi');
});
