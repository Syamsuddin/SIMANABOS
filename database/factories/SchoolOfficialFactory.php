<?php

namespace Database\Factories;

use App\Enums\OfficialRole;
use App\Models\School;
use App\Models\SchoolOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolOfficial>
 */
class SchoolOfficialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'role' => OfficialRole::Bendahara,
            'name' => fake()->name(),
            'nip' => null,
            'started_at' => '2026-01-01',
            'ended_at' => null,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['approved_at' => null, 'approved_by' => null]);
    }
}
