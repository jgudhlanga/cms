<?php

namespace Database\Seeders;

use Database\Seeders\AcademicCalendars\AcademicCalendarOptionSeeder;
use Database\Seeders\Acl\PermissionsTableSeeder;
use Database\Seeders\Acl\RolesTableSeeder;
use Illuminate\Database\Seeder;

class DeploymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            AcademicCalendarOptionSeeder::class,
        ]);
    }
}
