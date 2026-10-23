<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('membuat admin dinas yang wajib ganti sandi', function () {
    $this->artisan('simanabos:create-admin', ['--username' => 'admin.dinas', '--name' => 'Admin Dinas HSS'])
        ->expectsQuestion('Kata sandi sementara (min. 10 karakter, huruf dan angka)', 'Rahasia12345')
        ->expectsQuestion('Ulangi kata sandi', 'Rahasia12345')
        ->assertSuccessful();

    $user = User::query()->where('username', 'admin.dinas')->firstOrFail();
    expect($user->hasRole(RoleName::AdminDinas->value))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('Rahasia12345', $user->password))->toBeTrue();
});

it('menolak sandi lemah, konfirmasi beda, dan username terpakai', function (string $username, string $password, string $confirm) {
    User::factory()->create(['username' => 'sudah.ada']);

    $this->artisan('simanabos:create-admin', ['--username' => $username, '--name' => 'Admin'])
        ->expectsQuestion('Kata sandi sementara (min. 10 karakter, huruf dan angka)', $password)
        ->expectsQuestion('Ulangi kata sandi', $confirm)
        ->assertFailed();

    expect(User::query()->where('username', $username)->whereHas('roles')->exists())->toBeFalse();
})->with([
    'sandi pendek' => ['admin.baru', 'abc123', 'abc123'],
    'tanpa angka' => ['admin.baru', 'RahasiaSekali', 'RahasiaSekali'],
    'konfirmasi beda' => ['admin.baru', 'Rahasia12345', 'Rahasia99999'],
    'username terpakai' => ['sudah.ada', 'Rahasia12345', 'Rahasia12345'],
]);
