<?php

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Allocation> */
class AllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'fund_source_id' => fn () => FundSource::query()->create([
                'code' => fake()->unique()->bothify('DANA_####'),
                'name' => 'Sumber Dana Uji',
            ])->id,
            'amount' => '100000000.00',
            'active_from_month' => 1,
        ];
    }
}
