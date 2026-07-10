<?php

namespace Database\Seeders;

use Database\Seeders\AcademicCalendars\AcademicYearOptionSeeder;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Database\Seeders\Acl\ModulesTableSeeder;
use Database\Seeders\Acl\PermissionsTableSeeder;
use Database\Seeders\Acl\RolesTableSeeder;
use Database\Seeders\Institution\AssessmentTypesTableSeeder;
use Database\Seeders\Students\StudentEnrolmentStatusSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\HMS\HostelAmenitySeeder;
use Database\Seeders\HMS\HostelRoomSectionSeeder;
use Database\Seeders\HMS\RoomSectionAmenitySeeder;

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
        ]);
    }
}
