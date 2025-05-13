<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\DivisionEnum;
use App\Models\Institution\Division;
use Illuminate\Database\Seeder;

class DivisionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => DivisionEnum::BUSINESS->value, 'position' => 1 ],
            ['name' => DivisionEnum::MANAGEMENT->value, 'position' => 2 ],
            ['name' => DivisionEnum::PEDAGOGICS->value, 'position' => 3 ],
            ];
        foreach ($data as $row) {
            Division::create(['name' => $row['name'], 'position' => $row['position']]);
        }
    }
}
