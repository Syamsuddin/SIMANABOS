<?php

namespace Database\Factories;

use App\Enums\SchoolLevel;
use App\Enums\SchoolStatus;
use App\Models\District;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'npsn' => (string) fake()->unique()->numerify('30######'),
            'name' => 'SD Negeri '.fake()->unique()->numerify('Contoh ###'),
            'level' => SchoolLevel::SD,
            'status' => SchoolStatus::Negeri,
            'district_id' => District::query()->create([
                'code' => fake()->unique()->bothify('KC-####'),
                'name' => 'Kecamatan '.fake()->unique()->word(),
            ])->id,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
