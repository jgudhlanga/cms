<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\CommunicationMethodEnum;
use App\Models\Shared\CommunicationMethod;
use Illuminate\Database\Seeder;

class CommunicationMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CommunicationMethodEnum::cases() as $row) {
            $exist = CommunicationMethod::where('title', $row->value)->first();
            if (! $exist instanceof CommunicationMethod) {
                CommunicationMethod::create(['title' => $row->value]);
            }
        }
    }
}
