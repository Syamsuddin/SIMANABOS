<?php

use App\Enums\RoleName;

it('menandai hanya operator dan kepala sekolah sebagai role sekolah', function (RoleName $role, bool $expected) {
    expect($role->isSchoolRole())->toBe($expected);
})->with([
    [RoleName::AdminDinas, false],
    [RoleName::PemeriksaDinas, false],
    [RoleName::PimpinanDinas, false],
    [RoleName::OperatorSekolah, true],
    [RoleName::KepalaSekolah, true],
]);

it('memakai nama role persis seperti docs/05', function () {
    expect(array_map(fn (RoleName $r) => $r->value, RoleName::cases()))->toBe([
        'admin_dinas', 'pemeriksa_dinas', 'pimpinan_dinas', 'operator_sekolah', 'kepala_sekolah',
    ]);
});

it('memberi label bahasa Indonesia untuk setiap role', function () {
    foreach (RoleName::cases() as $role) {
        expect($role->label())->not->toBeEmpty();
    }
});
