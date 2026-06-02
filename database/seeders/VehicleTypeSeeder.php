<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => '4WH', 'slug' => '4wh'],
            ['name' => '2WH', 'slug' => '2wh']
        ];

        foreach ($data as $item) {
            \App\Models\VehicleType::create($item);
        }
    }
}
