<?php

namespace Database\Factories;

use App\Models\EstimasiArrival;
use App\Models\TypeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimasiArrival>
 */
class EstimasiArrivalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hour = fake()->numberBetween(0, 20);

        return [
            'type_slot_id' => TypeSlot::factory(),
            'estimasi_arrival' => sprintf('%02d:00:00', $hour),
            'status' => true,
        ];
    }
}
