<?php

namespace Database\Seeders\Provinces;

use App\Enums\ProvinceEnum;
use App\Models\Provinces\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach (ProvinceEnum::cases() as $row) {
            Province::create(['title' => $row->value]);
        }
    }
}
