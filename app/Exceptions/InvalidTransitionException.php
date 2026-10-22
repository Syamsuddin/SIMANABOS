<?php

namespace App\Exceptions;

use App\Enums\ReportStatus;
use RuntimeException;

/** Transisi status laporan yang tidak ada di docs/06 §D, atau status sudah berubah sejak halaman dimuat. */
class InvalidTransitionException extends RuntimeException
{
    public function __construct(public readonly ReportStatus $from, public readonly ReportStatus $to)
    {
        parent::__construct("Transisi {$from->value} → {$to->value} tidak diizinkan.");
    }
}
