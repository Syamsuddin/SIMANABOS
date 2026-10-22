<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MonthlyReport> */
class MonthlyReportFactory extends Factory
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
            'month' => 1,
        ];
    }
}
