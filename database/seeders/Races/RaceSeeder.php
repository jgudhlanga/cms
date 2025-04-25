<?php

namespace Database\Seeders\Races;

use App\Enums\RaceEnum;
use App\Models\Races\Race;
use Illuminate\Database\Seeder;

class RaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RaceEnum::cases() as $row) {
            Race::create(['title' => $row->value]);
        }
    }
}
