<?php

namespace Database\Seeders\Statuses;

use App\Enums\Shared\MaritalStatusEnum;
use App\Models\Shared\MaritalStatus;
use Illuminate\Database\Seeder;

class MaritalStatusSeeder extends Seeder
{

    public function run(): void
    {
        foreach (MaritalStatusEnum::cases() as $row) {
            MaritalStatus::create(['title' => $row->value]);
        }
    }
}
