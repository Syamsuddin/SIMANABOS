<?php

namespace Database\Factories;

use App\Enums\FiscalYearStatus;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FiscalYear> */
class FiscalYearFactory extends Factory
{
    public function definition(): array
    {
        return [
            'year' => fake()->unique()->numberBetween(2030, 2090),
            'status' => FiscalYearStatus::Open,
            'default_deadline_day' => 10,
        ];
    }
}
