<?php

use App\Enums\RoleName;
use App\Models\SchoolOfficial;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;

it('mencatat perubahan user dengan nilai lama, baru, dan pelaku', function () {
    $admin = userWithRole(RoleName::AdminDinas);
    $user = User::factory()->create(['name' => 'Nama Lama']);

    $this->actingAs($admin);
    $user->update(['name' => 'Nama Baru']);

    $log = Activity::query()->where('subject_type', User::class)->where('subject_id', $user->id)->where('event', 'updated')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->attribute_changes['old']['name'])->toBe('Nama Lama')
        ->and($log->attribute_changes['attributes']['name'])->toBe('Nama Baru')
        ->and($log->created_at)->not->toBeNull();
});

it('tidak pernah mencatat password', function () {
    $user = User::factory()->create();
    $user->update(['password' => 'rahasia-baru-123', 'name' => 'Ganti']);

    $logs = Activity::query()->get();
    expect($logs)->not->toBeEmpty();
    foreach ($logs as $log) {
        foreach (['attributes', 'old'] as $key) {
            expect(array_keys($log->attribute_changes[$key] ?? []))->not->toContain('password')->not->toContain('remember_token');
        }
    }
    expect($logs->toJson())->not->toContain('rahasia-baru-123');
});

it('mencatat perubahan pengelola sekolah', function () {
    $admin = userWithRole(RoleName::AdminDinas);
    $official = SchoolOfficial::factory()->create(['name' => 'Pejabat Lama']);

    $this->actingAs($admin);
    $official->update(['name' => 'Pejabat Baru']);

    $log = Activity::query()->where('subject_type', SchoolOfficial::class)->where('event', 'updated')->latest('id')->first();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->attribute_changes['old']['name'])->toBe('Pejabat Lama')
        ->and($log->attribute_changes['attributes']['name'])->toBe('Pejabat Baru');
});

it('tidak menyediakan route ubah atau hapus audit', function () {
    $uris = collect(Route::getRoutes())->map(fn ($r) => $r->uri())->filter(fn ($u) => stripos($u, 'activity') !== false || stripos($u, 'audit') !== false);

    expect($uris)->toBeEmpty();
});
