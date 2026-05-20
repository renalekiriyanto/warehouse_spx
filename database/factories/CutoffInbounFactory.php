<?php

namespace Database\Factories;

use App\Models\CutoffInboun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CutoffInboun>
 */
class CutoffInbounFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word() . ' Cutoff';
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'is_active' => fake()->boolean(80), // 80% chance true
            'time_start' => fake()->time('H:i:s'),
            'time_end' => fake()->time('H:i:s'),
        ];
    }
}
