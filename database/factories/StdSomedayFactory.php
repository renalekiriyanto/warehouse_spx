<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\StdSomeday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StdSomeday>
 */
class StdSomedayFactory extends Factory
{
    protected $model = StdSomeday::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date_time' => fake()->dateTimeBetween('-30 days', 'now'),
            'awb' => 'AWB' . fake()->unique()->numerify('##########'),
            'id_driver' => null,
            'status' => fake()->randomElement([
                'LMHub_Received',
                'LMHub_Assigned',
                'LMHub_Assigning',
                'Return_LMHub_Packed',
                'Return_LMHub_Received',
                'Delivering',
                'OnHold',
                'Delivered',
            ]),
        ];
    }

    /**
     * Indicate that the record has a driver assigned.
     */
    public function withDriver(): static
    {
        return $this->state(fn (array $attributes) => [
            'id_driver' => Driver::factory(),
        ]);
    }
}
