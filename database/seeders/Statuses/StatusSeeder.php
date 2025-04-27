<?php

namespace Database\Seeders\Statuses;

use App\Enums\StatusEnum;
use App\Models\Statuses\Status;
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
