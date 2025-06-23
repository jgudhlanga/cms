<?php

namespace Database\Seeders\Statuses;

use App\Enums\Shared\StatusEnum;
use App\Models\Shared\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{

    public function run(): void
    {

        foreach (StatusEnum::cases() as $row) {
			Status::create(['title' => $row->value]);
        }
    }
}
