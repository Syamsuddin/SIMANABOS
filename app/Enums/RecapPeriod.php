<?php

namespace App\Enums;

enum RecapPeriod: string
{
    case Tw1 = 'tw1';
    case Tw2 = 'tw2';
    case Tw3 = 'tw3';
    case Tw4 = 'tw4';
    case Sem1 = 'sem1';
    case Sem2 = 'sem2';
    case Tahun = 'tahun';

    public function label(): string
    {
        return match ($this) {
            self::Tw1 => 'Triwulan I',
            self::Tw2 => 'Triwulan II',
            self::Tw3 => 'Triwulan III',
            self::Tw4 => 'Triwulan IV',
            self::Sem1 => 'Semester I',
            self::Sem2 => 'Semester II',
            self::Tahun => 'Tahun penuh',
        };
    }

    /** @return array{0: int, 1: int} bulan pertama dan terakhir periode */
    public function months(): array
    {
        return match ($this) {
            self::Tw1 => [1, 3],
            self::Tw2 => [4, 6],
            self::Tw3 => [7, 9],
            self::Tw4 => [10, 12],
            self::Sem1 => [1, 6],
            self::Sem2 => [7, 12],
            self::Tahun => [1, 12],
        };
    }
}
