<?php

namespace Database\Seeders;

use Database\Seeders\Acl\ModulesTableSeeder;
use Database\Seeders\Acl\PermissionsTableSeeder;
use Database\Seeders\Acl\RolesTableSeeder;
use Database\Seeders\AddressTypes\AddressTypesTableSeeder;
use Database\Seeders\Communications\CommunicationMethodSeeder;
use Database\Seeders\Countries\CountrySeeder;
use Database\Seeders\Genders\GenderSeeder;
use Database\Seeders\Languages\LanguageSeeder;
use Database\Seeders\Payments\PaymentDaySeeder;
use Database\Seeders\Payments\PaymentFrequencySeeder;
use Database\Seeders\Payments\PaymentMethodSeeder;
use Database\Seeders\Provinces\ProvinceSeeder;
use Database\Seeders\Races\RaceSeeder;
use Database\Seeders\Statuses\StatusSeeder;
use Database\Seeders\Tenants\TenantsTableSeeder;
use Database\Seeders\Titles\TitleSeeder;
use Database\Seeders\Users\UsersTableSeeder;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantsTableSeeder::class,
            ModulesTableSeeder::class,
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            CommunicationMethodSeeder::class,
            CountrySeeder::class,
            PaymentFrequencySeeder::class,
            PaymentMethodSeeder::class,
            PaymentDaySeeder::class,
            GenderSeeder::class,
            LanguageSeeder::class,
            ProvinceSeeder::class,
            StatusSeeder::class,
            RaceSeeder::class,
            TitleSeeder::class,
            AddressTypesTableSeeder::class,
        ]);
    }
}
