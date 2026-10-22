<?php

use App\Enums\RoleName;
use App\Models\AppSetting;
use Spatie\Activitylog\Models\Activity;

it('admin melihat dan mengubah pengaturan persetujuan kepala sekolah, perubahan tercatat di audit', function () {
    $admin = userWithRole(RoleName::AdminDinas);

    $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Persetujuan kepala sekolah');
    expect(AppSetting::requiresPrincipalApproval())->toBeFalse();

    $this->put(route('admin.settings.update'), ['require_principal_approval' => '1', 'default_deadline_day' => '10', 'reminder_days_before' => '3'])->assertRedirect(route('admin.settings.index'));
    expect(AppSetting::requiresPrincipalApproval())->toBeTrue();

    $log = Activity::query()->where('description', 'Pengaturan diubah')->latest('id')->firstOrFail();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->properties['old'])->toBeFalse()
        ->and($log->properties['attributes'])->toBeTrue();

    $this->put(route('admin.settings.update'), ['require_principal_approval' => '0', 'default_deadline_day' => '10', 'reminder_days_before' => '3']);
    expect(AppSetting::requiresPrincipalApproval())->toBeFalse();
});

it('peran selain admin ditolak', function () {
    foreach ([RoleName::PemeriksaDinas, RoleName::PimpinanDinas] as $role) {
        $this->actingAs(userWithRole($role))->get(route('admin.settings.index'))->assertForbidden();
        $this->put(route('admin.settings.update'), ['require_principal_approval' => '1'])->assertForbidden();
    }
    expect(AppSetting::requiresPrincipalApproval())->toBeFalse();
});

it('nilai bukan boolean ditolak validasi', function () {
    $this->actingAs(userWithRole(RoleName::AdminDinas))
        ->put(route('admin.settings.update'), ['require_principal_approval' => 'abc'])
        ->assertSessionHasErrors('require_principal_approval');
});
