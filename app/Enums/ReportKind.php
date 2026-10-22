<?php

namespace App\Enums;

enum ReportKind: string
{
    case Monthly = 'monthly';
    case Rkas = 'rkas';
    case Stage = 'stage';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Laporan bulanan',
            self::Rkas => 'RKAS',
            self::Stage => 'Laporan tahap',
            self::Annual => 'Laporan tahunan',
        };
    }
}
