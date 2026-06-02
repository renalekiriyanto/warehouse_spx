<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'IPI'],
            ['name' => 'IPL'],
            ['name' => 'BAS'],
            ['name' => 'MAYROBIN'],
            ['name' => 'PSD'],
        ];

        foreach ($data as $item) {
            \App\Models\Agency::create($item);
        }
    }
}
