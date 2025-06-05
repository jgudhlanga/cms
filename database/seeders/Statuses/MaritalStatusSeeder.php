<?php

namespace Database\Seeders\Statuses;

use App\Enums\MaritalStatusEnum;
use App\Models\Statuses\MaritalStatus;
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
