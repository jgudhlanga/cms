<?php

namespace Database\Seeders\Students;

use App\Models\Students\StudentEnrolmentStatus;
use Illuminate\Database\Seeder;

class StudentEnrolmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Active',
                'description' => 'The student is currently registered, attending classes, and actively participating in the program.',
            ],
            [
                'name' => 'Completed',
                'description' => 'The student has passed all modules/subjects and has a "Full Award".',
            ],
            [
                'name' => 'Repeat/Re-write',
                'description' => 'The student failed one or more modules in a previous session and is retaking them to obtain a full award.',
            ],
            [
                'name' => 'Deferred/Postponed',
                'description' => 'The student, usually due to failure to complete registration or pay fees, has postponed their studies to a later session.',
            ],
        ];

        foreach ($rows as $row) {
            StudentEnrolmentStatus::query()->updateOrCreate(
                ['name' => $row['name']],
                ['description' => $row['description']],
            );
        }
    }
}
