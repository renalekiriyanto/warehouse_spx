<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\VehicleType;
use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_driver' => 'DRV' . fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'id_vehicle_type' => VehicleType::factory(),
            'id_agency' => Agency::factory(),
            'contract_type' => fake()->randomElement(['permanent', 'contract']),
            'status' => 'active',
        ];
    }
}
