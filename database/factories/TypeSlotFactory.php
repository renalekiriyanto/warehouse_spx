<?php

namespace Database\Factories;

use App\Models\TypeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TypeSlot>
 */
class TypeSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word() . ' Slot';
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'is_additional' => fake()->boolean(20), // 20% chance true
        ];
    }
}
