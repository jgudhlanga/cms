<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\ProvinceEnum;
use App\Models\Shared\Province;
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
