<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\ReligionEnum;
use App\Models\Shared\Religion;
use Illuminate\Database\Seeder;

class ReligionTableSeeder extends Seeder
{

    public function run(): void
    {
        foreach (ReligionEnum::cases() as $row) {
            Religion::create(['name' => $row->value]);
        }
    }
}
