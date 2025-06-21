<?php

namespace Database\Seeders\Religions;

use App\Enums\ReligionEnum;
use App\Models\Religions\Religion;
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
