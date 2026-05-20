<?php

namespace Database\Factories;

use App\Models\Projection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Projection>
 */
class ProjectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'projected_inbound' => fake()->numberBetween(100, 5000),
            'date_inbound' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }
}
