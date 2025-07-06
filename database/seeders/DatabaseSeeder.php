<?php

namespace Database\Seeders;

use Database\Seeders\Acl\ModulesTableSeeder;
use Database\Seeders\Acl\PermissionsTableSeeder;
use Database\Seeders\Acl\RolesTableSeeder;
use Database\Seeders\Institution\CoursesTableSeeder;
use Database\Seeders\Institution\DepartmentsTableSeeder;
use Database\Seeders\Institution\DivisionsTableSeeder;
use Database\Seeders\Institution\GradesTableSeeder;
use Database\Seeders\Institution\InstitutionDepartmentsTableSeeder;
use Database\Seeders\Institution\LevelsTableSeeder;
use Database\Seeders\Institution\ModesOfStudyTableSeeder;
use Database\Seeders\Institution\SubjectsTableSeeder;
use Database\Seeders\Shared\AcademicLevelsTableSeeder;
use Database\Seeders\Shared\AddressTypesTableSeeder;
use Database\Seeders\Shared\WorkflowStepActionSeeder;
use Database\Seeders\Shared\WorkflowStepsSeeder;
use Database\Seeders\Shared\CommunicationMethodSeeder;
use Database\Seeders\Shared\CountrySeeder;
use Database\Seeders\Shared\DistrictsTableSeeder;
use Database\Seeders\Shared\EmploymentTypesSeeder;
use Database\Seeders\Shared\GenderSeeder;
use Database\Seeders\Shared\IdTypeSeeder;
use Database\Seeders\Shared\LanguageSeeder;
use Database\Seeders\Shared\PaymentDaySeeder;
use Database\Seeders\Shared\PaymentFrequencySeeder;
use Database\Seeders\Shared\PaymentMethodSeeder;
use Database\Seeders\Shared\ProvinceSeeder;
use Database\Seeders\Shared\RaceSeeder;
use Database\Seeders\Shared\RelationshipsTableSeeder;
use Database\Seeders\Shared\ReligionTableSeeder;
use Database\Seeders\Shared\SponsorTypeTableSeeder;
use Database\Seeders\Shared\TitleSeeder;
use Database\Seeders\Statuses\MaritalStatusSeeder;
use Database\Seeders\Statuses\StatusSeeder;
use Database\Seeders\Tenants\TenantsTableSeeder;
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
            IdTypeSeeder::class,
            TitleSeeder::class,
            RaceSeeder::class,
            StatusSeeder::class,
            WorkflowStepsSeeder::class,
            WorkflowStepActionSeeder::class,
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
            SponsorTypeTableSeeder::class,
            MaritalStatusSeeder::class,
            AddressTypesTableSeeder::class,
            CoursesTableSeeder::class,
            DepartmentsTableSeeder::class,
            DivisionsTableSeeder::class,
            GradesTableSeeder::class,
            LevelsTableSeeder::class,
            AcademicLevelsTableSeeder::class,
            RelationshipsTableSeeder::class,
            SubjectsTableSeeder::class,
            ModesOfStudyTableSeeder::class,
            InstitutionDepartmentsTableSeeder::class,
            EmploymentTypesSeeder::class,
        ]);
    }
}
