<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case DalamPemeriksaan = 'dalam_pemeriksaan';
    case PerluPerbaikan = 'perlu_perbaikan';
    case SelesaiDiperiksa = 'selesai_diperiksa';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Diajukan',
            self::DalamPemeriksaan => 'Dalam pemeriksaan',
            self::PerluPerbaikan => 'Perlu perbaikan',
            self::SelesaiDiperiksa => 'Selesai diperiksa',
        };
    }
}
