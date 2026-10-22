<?php

namespace Database\Seeders;

use App\Models\FundSource;
use Illuminate\Database\Seeder;

class FundSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['REGULER', 'BOS Reguler'], ['KINERJA', 'BOS Kinerja']] as [$code, $name]) {
            FundSource::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }
}
