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
                'description' => 'The student is currently registered on this semester/phase.',
            ],
            [
                'name' => 'Absent',
                'description' => 'The student was absent from the examination session.',
            ],
            [
                'name' => 'Award',
                'description' => 'The student has been awarded the qualification for this semester/phase.',
            ],
            [
                'name' => 'Deferred',
                'description' => 'The student has deferred their studies to a later session.',
            ],
            [
                'name' => 'Disqualified',
                'description' => 'The student has been disqualified from this examination session.',
            ],
            [
                'name' => 'Proceed',
                'description' => 'The student has passed and may proceed to the next semester/phase.',
            ],
            [
                'name' => 'Referred',
                'description' => 'The student has been referred and must retake one or more modules.',
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
