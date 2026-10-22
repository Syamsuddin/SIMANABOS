<?php

use App\Enums\RoleName;
use App\Models\School;
use App\Models\SchoolOfficial;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->admin = userWithRole(RoleName::AdminDinas);
    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);
    $this->payload = ['role' => 'bendahara', 'name' => 'Bendahara Baru', 'nip' => '198001012005011001', 'started_at' => '2026-07-01'];
});

it('pergantian bendahara oleh admin: baris lama ber-ended_at dan hanya satu bendahara aktif', function () {
    $old = SchoolOfficial::factory()->create(['school_id' => $this->school->id, 'started_at' => '2026-01-01']);

    $this->actingAs($this->admin)->post(route('admin.schools.officials.store', $this->school), $this->payload)->assertRedirect();

    expect($old->fresh()->ended_at->toDateString())->toBe('2026-06-30');
    $active = SchoolOfficial::query()->active()->where('school_id', $this->school->id)->where('role', 'bendahara')->get();
    expect($active)->toHaveCount(1)->and($active->first()->name)->toBe('Bendahara Baru')
        ->and($active->first()->approved_by)->toBe($this->admin->id);
});

it('usulan operator menunggu pengesahan dan belum mengakhiri pengelola lama', function () {
    $old = SchoolOfficial::factory()->create(['school_id' => $this->school->id, 'started_at' => '2026-01-01']);

    $this->actingAs($this->operator)->post(route('school.officials.store'), $this->payload)->assertRedirect(route('school.profile.show'));

    $pending = SchoolOfficial::query()->pending()->first();
    expect($pending)->not->toBeNull()->and($old->fresh()->ended_at)->toBeNull();

    // usulan kedua untuk peran sama ditolak selama masih menunggu
    $this->actingAs($this->operator)->post(route('school.officials.store'), $this->payload)->assertSessionHasErrors('role');

    $this->actingAs($this->admin)->post(route('admin.officials.approve', $pending))->assertRedirect();
    expect($old->fresh()->ended_at->toDateString())->toBe('2026-06-30')
        ->and($pending->fresh()->isPending())->toBeFalse()
        ->and(SchoolOfficial::query()->active()->where('role', 'bendahara')->count())->toBe(1);
});

it('operator tidak dapat mengesahkan atau menolak usulan', function () {
    $pending = SchoolOfficial::factory()->pending()->create(['school_id' => $this->school->id]);

    $this->actingAs($this->operator)->post(route('admin.officials.approve', $pending))->assertForbidden();
    $this->actingAs($this->operator)->delete(route('admin.officials.reject', $pending))->assertForbidden();
    expect($pending->fresh()->isPending())->toBeTrue();
});

it('admin menolak usulan, tetapi baris yang sudah sah tidak dapat dihapus', function () {
    $pending = SchoolOfficial::factory()->pending()->create(['school_id' => $this->school->id]);
    $approved = SchoolOfficial::factory()->create(['school_id' => $this->school->id, 'role' => 'operator']);

    $this->actingAs($this->admin)->delete(route('admin.officials.reject', $pending))->assertRedirect();
    expect(SchoolOfficial::find($pending->id))->toBeNull();

    $this->actingAs($this->admin)->delete(route('admin.officials.reject', $approved))->assertForbidden();
    expect(SchoolOfficial::find($approved->id))->not->toBeNull();
});

it('menolak tanggal mulai yang tidak setelah pengelola yang menjabat', function () {
    SchoolOfficial::factory()->create(['school_id' => $this->school->id, 'started_at' => '2026-07-01']);

    $this->actingAs($this->admin)
        ->post(route('admin.schools.officials.store', $this->school), $this->payload)
        ->assertSessionHasErrors('started_at');
    expect(SchoolOfficial::query()->active()->count())->toBe(1);
});

it('memvalidasi peran, NIP, dan tanggal', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.schools.officials.store', $this->school), ['role' => 'satpam', 'name' => '', 'nip' => '123', 'started_at' => 'bukan-tanggal'])
        ->assertSessionHasErrors(['role', 'name', 'nip', 'started_at']);
});
