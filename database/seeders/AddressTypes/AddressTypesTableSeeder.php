<?php

namespace Database\Seeders\AddressTypes;

use App\Enums\AddressTypeEnum;
use App\Models\AddressTypes\AddressType;
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
