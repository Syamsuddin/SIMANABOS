<?php

use App\Enums\RoleName;

it('mengirim header keamanan docs/21 pada halaman publik dan terautentikasi', function () {
    $guest = $this->get(route('login'));
    $guest->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'same-origin')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeaderMissing('Strict-Transport-Security');

    $this->actingAs(userWithRole(RoleName::AdminDinas, ['must_change_password' => false]))
        ->get(route('home'))->assertOk()->assertHeader('X-Frame-Options', 'DENY');
});

it('mengirim HSTS hanya lewat HTTPS', function () {
    $this->get('https://localhost/masuk')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('memberi X-Request-Id baru, atau meneruskan yang aman dari proxy', function () {
    $fresh = $this->get(route('login'))->headers->get('X-Request-Id');
    expect($fresh)->toMatch('/^[0-9a-f-]{36}$/');

    $this->withHeader('X-Request-Id', 'nginx-abc12345')->get(route('login'))->assertHeader('X-Request-Id', 'nginx-abc12345');

    $unsafe = $this->withHeader('X-Request-Id', "bad\nvalue<script>")->get(route('login'))->headers->get('X-Request-Id');
    expect($unsafe)->toMatch('/^[0-9a-f-]{36}$/');
});

it('memiliki kanal log errors level error dengan retensi 30 hari', function () {
    expect(config('logging.channels.errors.level'))->toBe('error')
        ->and(config('logging.channels.errors.driver'))->toBe('daily')
        ->and((int) config('logging.channels.errors.max_files'))->toBe(30)
        ->and((int) config('logging.channels.daily.max_files'))->toBe(30);
});
