<?php

use App\Enums\RoleName;
use App\Models\User;

it('menampilkan halaman masuk untuk tamu', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Username')
        ->assertSee('Sandi');
});

it('mengalihkan tamu dari halaman terlindungi ke halaman masuk', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get('/')->assertRedirect('/beranda');
});

it('memasukkan pengguna dengan kredensial benar dan mencatat waktu masuk', function () {
    $user = userWithRole(RoleName::AdminDinas, ['username' => 'admin.uji']);

    $this->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'Sandi12345'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('menolak sandi salah tanpa membuka sesi', function () {
    userWithRole(RoleName::AdminDinas, ['username' => 'admin.uji']);

    $this->from(route('login'))
        ->post(route('login.store'), ['username' => 'admin.uji', 'password' => 'salah-sekali'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['username' => trans('auth.failed')]);

    $this->assertGuest();
});

it('menolak username yang tidak dikenal dengan pesan yang sama', function () {
    $this->post(route('login.store'), ['username' => 'tidak.ada', 'password' => 'Sandi12345'])
        ->assertSessionHasErrors(['username' => trans('auth.failed')]);

    $this->assertGuest();
});

it('menolak akun nonaktif walau sandi benar', function () {
    userWithRole(RoleName::OperatorSekolah, ['username' => 'operator.mati', 'is_active' => false]);

    $this->post(route('login.store'), ['username' => 'operator.mati', 'password' => 'Sandi12345'])
        ->assertSessionHasErrors(['username' => trans('auth.inactive')]);

    $this->assertGuest();
});

it('mewajibkan username dan sandi', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors(['username', 'password']);
});

it('mengakhiri sesi akun yang dinonaktifkan saat sedang masuk', function () {
    $user = userWithRole(RoleName::AdminDinas);

    $this->actingAs($user)->get(route('home'))->assertOk();

    $user->update(['is_active' => false]);

    $this->get(route('home'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['username' => trans('auth.inactive')]);

    $this->assertGuest();
});

it('keluar dan menghapus sesi', function () {
    $this->actingAs(userWithRole(RoleName::AdminDinas))
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('mengalihkan pengguna yang sudah masuk menjauh dari halaman masuk', function () {
    $this->actingAs(userWithRole(RoleName::AdminDinas))
        ->get(route('login'))
        ->assertRedirect(route('home'));
});

it('merender beranda untuk setiap role', function (RoleName $role) {
    $this->actingAs(userWithRole($role, ['last_login_at' => now()]))
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Beranda')
        ->assertSee($role->label());
})->with(RoleName::cases());

it('tidak menyimpan sandi sebagai teks biasa', function () {
    $user = User::factory()->create(['password' => 'Sandi12345']);

    expect($user->getRawOriginal('password'))->not->toBe('Sandi12345')
        ->and(password_verify('Sandi12345', $user->getRawOriginal('password')))->toBeTrue();
});
