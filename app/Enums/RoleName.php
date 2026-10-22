<?php

namespace App\Enums;

enum RoleName: string
{
    case AdminDinas = 'admin_dinas';
    case PemeriksaDinas = 'pemeriksa_dinas';
    case PimpinanDinas = 'pimpinan_dinas';
    case OperatorSekolah = 'operator_sekolah';
    case KepalaSekolah = 'kepala_sekolah';

    public function isSchoolRole(): bool
    {
        return match ($this) {
            self::OperatorSekolah, self::KepalaSekolah => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AdminDinas => 'Admin Dinas',
            self::PemeriksaDinas => 'Pemeriksa Dinas',
            self::PimpinanDinas => 'Pimpinan Dinas',
            self::OperatorSekolah => 'Operator Sekolah',
            self::KepalaSekolah => 'Kepala Sekolah',
        };
    }
}
