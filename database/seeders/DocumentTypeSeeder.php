<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['REALISASI', 'Laporan Realisasi', 'monthly'],
            ['BKU', 'Buku Kas Umum (BKU)', 'monthly'],
            ['SPTJM', 'SPTJM', 'monthly'],
            ['REKAP_BMD', 'Rekap BMD', 'monthly'],
            ['LAMPIRAN', 'Lampiran Lain', 'monthly'],
            ['RKAS', 'RKAS Disahkan', 'periodic'],
            ['TAHAP', 'Laporan Tahap', 'periodic'],
            ['TAHUNAN', 'Laporan Tahunan', 'periodic'],
            ['BA', 'Berita Acara', 'periodic'],
        ];

        foreach ($types as [$code, $name, $scope]) {
            DocumentType::query()->firstOrCreate(['code' => $code], ['name' => $name, 'scope' => $scope]);
        }
    }
}
