<?php

use App\Enums\RoleName;

it('menolak percobaan ke-6 dalam semenit dengan pesan tunggu walau sandi benar', function () {
    userWithRole(RoleName::AdminDinas, ['username' => 'admin.uji']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'salah-terus'])
            ->assertSessionHasErrors(['username' => trans('auth.failed')]);
    }

    $this->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'Sandi12345'])
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('menampilkan pesan tunggu di halaman masuk saat dibatasi', function () {
    userWithRole(RoleName::AdminDinas, ['username' => 'admin.uji']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'salah-terus']);
    }

    $this->followingRedirects()
        ->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'Sandi12345'])
        ->assertOk()
        ->assertSee('Terlalu banyak percobaan masuk')
        ->assertSee('detik');
});

it('menghitung batas per username sehingga akun lain tidak ikut terblokir', function () {
    userWithRole(RoleName::AdminDinas, ['username' => 'admin.uji']);
    userWithRole(RoleName::PemeriksaDinas, ['username' => 'pemeriksa.uji']);

    foreach (range(1, 6) as $attempt) {
        $this->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'salah-terus']);
    }

    $this->post(route('login.store'), ['username' => 'pemeriksa.uji', 'password' => 'Sandi12345'])
        ->assertRedirect(route('home'));
});

it('menganggap username tanpa membedakan huruf besar kecil sebagai kunci yang sama', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['username' => $attempt % 2 ? 'ADMIN.uji' : 'admin.UJI', 'password' => 'salah']);
    }

    $this->followingRedirects()
        ->post(route('login.store'), ['username' => 'Admin.Uji', 'password' => 'salah'])
        ->assertSee('Terlalu banyak percobaan masuk');
});
