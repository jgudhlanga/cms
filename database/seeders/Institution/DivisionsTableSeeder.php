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
        foreach (DivisionEnum::cases() as $row) {
            Division::create(['name' => $row->value]);
        }
    }
}
