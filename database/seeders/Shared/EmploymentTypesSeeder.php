<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\Shared\EmploymentType;
use Illuminate\Database\Seeder;

class EmploymentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (EmploymentTypeEnum::cases() as $type) {
            EmploymentType::create(
                [
                    'name' => $type->value,
                    'description' => $type->description(),
                ]
            );
        }
    }
}
