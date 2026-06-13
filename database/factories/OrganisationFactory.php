<?php

namespace Database\Factories;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organisation>
 */
class OrganisationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'short_code' => strtoupper(fake()->unique()->lexify('???')),
            'joined_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
