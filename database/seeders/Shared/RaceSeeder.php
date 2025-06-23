<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\RaceEnum;
use App\Models\Shared\Race;
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
