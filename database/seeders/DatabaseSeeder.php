<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data referensi + data uji lokal (docs/10). Menolak berjalan di produksi; produksi hanya memakai
     * seeder referensi per kelas dan `simanabos:create-admin`.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DatabaseSeeder berisi data uji dan tidak boleh dijalankan di produksi.');

            return;
        }

        $this->call([
            RoleSeeder::class,
            FundSourceSeeder::class,
            DocumentTypeSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
