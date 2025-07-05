<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\IdTypeEnum;
use App\Models\Shared\IdType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IdTypeSeeder extends Seeder
{

    public function run(): void
    {
        foreach (IdTypeEnum::cases() as $row) {
            IdType::create(['name' => $row->value, 'description' => $row->description()]);
        }
    }
}
