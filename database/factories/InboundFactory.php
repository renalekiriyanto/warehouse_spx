<?php

namespace Database\Factories;

use App\Models\Inbound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inbound>
 */
class InboundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date_inbound' => fake()->date(),
            'actual_arrival' => fake()->time('H:i:s'),
            'bulky' => fake()->numberBetween(0, 100),
            'total_order' => fake()->numberBetween(10, 1000),
        ];
    }
}
