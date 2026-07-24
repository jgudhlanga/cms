<?php

namespace Database\Seeders;

use Database\Seeders\AcademicCalendars\AcademicYearOptionSeeder;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Database\Seeders\Rbac\ModulesTableSeeder;
use Database\Seeders\Rbac\PermissionsTableSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Database\Seeders\HMS\HostelAmenitySeeder;
use Database\Seeders\HMS\HostelRoomSectionSeeder;
use Database\Seeders\HMS\RoomSectionAmenitySeeder;
use Database\Seeders\Institution\AssessmentTypesTableSeeder;
use Database\Seeders\Institution\ContinuousIntakePeriodSeeder;
use Database\Seeders\Students\StudentEnrolmentStatusSeeder;
use Illuminate\Database\Seeder;

class DeploymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ModulesTableSeeder::class,
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            AcademicYearOptionSeeder::class,
            ClassMetaDataTypeSeeder::class,
            StudentEnrolmentStatusSeeder::class,
            AssessmentTypesTableSeeder::class,
            HostelAmenitySeeder::class,
            HostelRoomSectionSeeder::class,
            RoomSectionAmenitySeeder::class,
            ContinuousIntakePeriodSeeder::class,
        ]);
    }
}
