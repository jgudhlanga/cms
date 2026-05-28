<?php

use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

function createStudentForFinanceQuery(User $user, string $studentNumber): Student
{
    $student = Student::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr '.$studentNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male '.$studentNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single '.$studentNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID '.$studentNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => $studentNumber,
        'student_number_generated' => $studentNumber,
    ]);

    DB::table('students')->where('id', $student->id)->update([
        'student_number' => $studentNumber,
        'student_number_generated' => $studentNumber,
    ]);

    return $student->fresh();
}
