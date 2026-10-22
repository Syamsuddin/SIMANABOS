<?php

namespace App\Enums;

enum SchoolLevel: string
{
    case SD = 'SD';
    case SMP = 'SMP';

    public function label(): string
    {
        return $this->value;
    }
}
