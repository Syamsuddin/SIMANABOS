<?php

namespace App\Enums;

enum PeriodicKind: string
{
    case Stage = 'stage';
    case Annual = 'annual';
    case Minutes = 'minutes';

    public function label(): string
    {
        return match ($this) {
            self::Stage => 'Laporan Tahap',
            self::Annual => 'Laporan Tahunan',
            self::Minutes => 'Berita Acara',
        };
    }

    /** Kode document_types yang otomatis ditandai pada berkas jenis ini. */
    public function documentTypeCode(): string
    {
        return match ($this) {
            self::Stage => 'TAHAP',
            self::Annual => 'TAHUNAN',
            self::Minutes => 'BA',
        };
    }

    /** Hanya laporan tahap dan tahunan punya angka pembanding terhadap Σ bulanan. */
    public function hasFigures(): bool
    {
        return $this !== self::Minutes;
    }
}
