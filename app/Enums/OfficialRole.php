<?php

namespace App\Enums;

enum OfficialRole: string
{
    case KepalaSekolah = 'kepala_sekolah';
    case Bendahara = 'bendahara';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::KepalaSekolah => 'Kepala sekolah',
            self::Bendahara => 'Bendahara',
            self::Operator => 'Operator',
        };
    }
}
