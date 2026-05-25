<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            ['name' => 'Slot 1', 'slug' => 'slot-1', 'is_additional' => false],
            ['name' => 'Slot 2', 'slug' => 'slot-2', 'is_additional' => false],
            ['name' => 'Slot 3', 'slug' => 'slot-3', 'is_additional' => false],
        ];

        foreach ($slots as $slot) {
            \App\Models\TypeSlot::firstOrCreate(['slug' => $slot['slug']], $slot);
        }
    }
}
