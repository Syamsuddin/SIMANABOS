<?php

namespace App\Enums;

enum AttentionTrigger: string
{
    case BelumMelapor = 'belum_melapor';
    case DokumenKurang = 'dokumen_kurang';
    case Selisih = 'selisih';
    case PerbaikanLama = 'perbaikan_lama';
    case AntreanLama = 'antrean_lama';
    case TinjauUlang = 'tinjau_ulang';
    case AnggaranMelebihiDana = 'anggaran_melebihi_dana';
    case SaldoAwalBelumDiverifikasi = 'saldo_awal_belum_diverifikasi';

    public function label(): string
    {
        return match ($this) {
            self::BelumMelapor => 'Belum melapor',
            self::DokumenKurang => 'Dokumen kurang',
            self::Selisih => 'Selisih saldo',
            self::PerbaikanLama => 'Perbaikan > 14 hari',
            self::AntreanLama => 'Antrean > 14 hari',
            self::TinjauUlang => 'Perlu tinjau ulang',
            self::AnggaranMelebihiDana => 'Anggaran > dana',
            self::SaldoAwalBelumDiverifikasi => 'Saldo awal belum diverifikasi',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::BelumMelapor => 'danger',
            self::DokumenKurang, self::Selisih, self::PerbaikanLama, self::AnggaranMelebihiDana => 'warning',
            self::AntreanLama, self::SaldoAwalBelumDiverifikasi => 'info',
            self::TinjauUlang => 'accent',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BelumMelapor => 'clock',
            self::DokumenKurang => 'paperclip',
            self::Selisih, self::AnggaranMelebihiDana => 'alert',
            self::PerbaikanLama, self::AntreanLama => 'clock',
            self::TinjauUlang => 'refresh',
            self::SaldoAwalBelumDiverifikasi => 'shield',
        };
    }
}
