<?php

namespace Database\Seeders;

use Database\Seeders\Acl\ModulesTableSeeder;
use Database\Seeders\Acl\PermissionsTableSeeder;
use Database\Seeders\Acl\RolesTableSeeder;
use Database\Seeders\AddressTypes\AddressTypesTableSeeder;
use Database\Seeders\Communications\CommunicationMethodSeeder;
use Database\Seeders\Countries\CountrySeeder;
use Database\Seeders\Districts\DistrictsTableSeeder;
use Database\Seeders\Genders\GenderSeeder;
use Database\Seeders\Institution\CoursesTableSeeder;
use Database\Seeders\Institution\DepartmentsTableSeeder;
use Database\Seeders\Institution\DivisionsTableSeeder;
use Database\Seeders\Institution\GradesTableSeeder;
use Database\Seeders\Institution\InstitutionDepartmentsTableSeeder;
use Database\Seeders\Institution\LevelsTableSeeder;
use Database\Seeders\Institution\ModesOfStudyTableSeeder;
use Database\Seeders\Institution\SubjectsTableSeeder;
use Database\Seeders\Languages\LanguageSeeder;
use Database\Seeders\Payments\PaymentDaySeeder;
use Database\Seeders\Payments\PaymentFrequencySeeder;
use Database\Seeders\Payments\PaymentMethodSeeder;
use Database\Seeders\Provinces\ProvinceSeeder;
use Database\Seeders\Races\RaceSeeder;
use Database\Seeders\Relationships\RelationshipsTableSeeder;
use Database\Seeders\Religions\ReligionTableSeeder;
use Database\Seeders\Statuses\MaritalStatusSeeder;
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
            GenderSeeder::class,
            TitleSeeder::class,
            RaceSeeder::class,
            UsersTableSeeder::class,
            CommunicationMethodSeeder::class,
            CountrySeeder::class,
            ReligionTableSeeder::class,
            PaymentFrequencySeeder::class,
            PaymentMethodSeeder::class,
            PaymentDaySeeder::class,
            LanguageSeeder::class,
            ProvinceSeeder::class,
            DistrictsTableSeeder::class,
            StatusSeeder::class,
            MaritalStatusSeeder::class,
            AddressTypesTableSeeder::class,
            CoursesTableSeeder::class,
            DepartmentsTableSeeder::class,
            DivisionsTableSeeder::class,
            GradesTableSeeder::class,
            LevelsTableSeeder::class,
            RelationshipsTableSeeder::class,
            SubjectsTableSeeder::class,
            ModesOfStudyTableSeeder::class,
            InstitutionDepartmentsTableSeeder::class,
        ]);
    }
}
