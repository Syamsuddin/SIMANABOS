<?php

use App\Enums\RoleName;
use Illuminate\Support\Facades\Hash;

it('mengalihkan akun baru ke halaman ganti sandi dari route lain', function () {
    $user = userWithRole(RoleName::OperatorSekolah, ['must_change_password' => true]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('password.change'))
        ->assertSessionHas('warning', trans('auth.must_change_password'));
});

it('masih membolehkan akun baru membuka halaman ganti sandi dan keluar', function () {
    $user = userWithRole(RoleName::OperatorSekolah, ['must_change_password' => true]);

    $this->actingAs($user)->get(route('password.change'))->assertOk()->assertSee('Sandi baru');
    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
});

it('mengganti sandi, mematikan penanda wajib ganti, lalu membuka akses', function () {
    $user = userWithRole(RoleName::AdminDinas, ['must_change_password' => true]);

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'Sandi12345',
            'password' => 'SandiBaru2026',
            'password_confirmation' => 'SandiBaru2026',
        ])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', trans('auth.password_changed'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('SandiBaru2026', $user->password))->toBeTrue();

    $this->get(route('home'))->assertOk();
});

it('menolak sandi saat ini yang salah', function () {
    $user = userWithRole(RoleName::AdminDinas, ['must_change_password' => true]);

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'bukan-sandi-saya',
            'password' => 'SandiBaru2026',
            'password_confirmation' => 'SandiBaru2026',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('menegakkan aturan sandi baru', function (string $newPassword, string $confirmation) {
    $user = userWithRole(RoleName::AdminDinas, ['must_change_password' => true]);

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'Sandi12345',
            'password' => $newPassword,
            'password_confirmation' => $confirmation,
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check('Sandi12345', $user->fresh()->password))->toBeTrue();
})->with([
    'kurang dari 10 karakter' => ['Pendek12', 'Pendek12'],
    'tanpa angka' => ['HurufSajaPanjang', 'HurufSajaPanjang'],
    'tanpa huruf' => ['12345678901234', '12345678901234'],
    'konfirmasi tidak cocok' => ['SandiBaru2026', 'SandiBaru2027'],
    'sama dengan sandi lama' => ['Sandi12345', 'Sandi12345'],
]);

it('menolak tamu', function () {
    $this->get(route('password.change'))->assertRedirect(route('login'));
    $this->put(route('password.update'), [])->assertRedirect(route('login'));
});
