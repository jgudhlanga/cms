<?php

namespace Database\Seeders\Communications;

use App\Enums\CommunicationMethodEnum;
use App\Models\Communications\CommunicationMethod;
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
