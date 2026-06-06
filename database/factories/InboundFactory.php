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
            'id_type_slot'  => \App\Models\TypeSlot::factory(),
            'date_inbound'  => fake()->date(),
            'actual_arrival' => fake()->time('H:i:s'),
            'total_order'   => fake()->numberBetween(10, 1000),
        ];
    }
}
