<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\AddressTypeEnum;
use App\Models\Shared\AddressType;
use Illuminate\Database\Seeder;

class AddressTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AddressTypeEnum::cases() as $row) {
            AddressType::create(['title' => $row->value]);
        }
    }
}
