<?php

namespace App\Enums;

enum AdjustmentKind: string
{
    case Realisasi = 'realisasi';
    case Penerimaan = 'penerimaan';
    case Lainnya = 'lainnya';
}
