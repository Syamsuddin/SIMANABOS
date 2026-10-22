<?php

namespace App\Enums;

enum SchoolStatus: string
{
    case Negeri = 'negeri';
    case Swasta = 'swasta';

    public function label(): string
    {
        return match ($this) {
            self::Negeri => 'Negeri',
            self::Swasta => 'Swasta',
        };
    }
}
