<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Shared\FeeType;
use Illuminate\Database\Seeder;

class FeeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (FeeTypeEnum::cases() as $row) {
            FeeType::create([
                'name' => $row->name(),
                'description' => $row->description(),
            ]);
        }
    }
}
