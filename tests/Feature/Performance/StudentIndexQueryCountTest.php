<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/*
 * The student index renders StudentResource per row. Its query count must not grow with the
 * number of students on the page.
 */

function studentIndexQueryCountEnrol(StudentApplication $application): void
{
    $suffix = Str::lower(Str::random(6));

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => Semester::query()->create(['slug' => 'qc-'.$suffix, 'name' => 'Semester '.$suffix])->id,
        'academic_calendar_id' => AcademicCalendar::query()->create([
            'calendar_year' => '2025/2026',
            'type' => 'semester',
            'opening_date' => '2026-01-01',
            'closing_date' => '2026-12-31',
        ])->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => StudentEnrolmentStatus::query()->firstOrCreate(
            ['slug' => 'active'],
            ['name' => 'Active', 'description' => 'Test'],
        )->id,
    ]);
}

/**
 * @return array{queries: int, rows: int, repeated: array<string, int>}
 */
function studentIndexQueryCountVisit(): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = test()->getJson(route('v1.students.index'))->assertOk();

    $queries = array_map(fn (array $query): string => $query['query'], DB::getQueryLog());
    DB::disableQueryLog();

    $tables = array_count_values(array_map(
        fn (string $sql): string => preg_match('/from\s+["`]?([a-z_]+)/i', $sql, $match) === 1 ? $match[1] : 'other',
        $queries,
    ));
    arsort($tables);

    return [
        'queries' => count($queries),
        'rows' => count($response->json('data') ?? []),
        'repeated' => array_filter($tables, fn (int $count): bool => $count > 1),
    ];
}

test('student index query count does not grow with the number of students', function () {
    $first = createVerifiedStudentApplication('QC-'.strtoupper(Str::random(6)));

    $user = User::factory()->create(['tenant_id' => $first->tenant_id]);
    $user->givePermissionTo('viewAny:students');
    Sanctum::actingAs($user);

    studentIndexQueryCountEnrol($first);
    studentIndexQueryCountEnrol(createVerifiedStudentApplication('QC-'.strtoupper(Str::random(6))));

    $fewStudents = studentIndexQueryCountVisit();

    foreach (range(1, 4) as $index) {
        studentIndexQueryCountEnrol(createVerifiedStudentApplication('QC-'.strtoupper(Str::random(6))));
    }

    $moreStudents = studentIndexQueryCountVisit();

    dump(['2 students' => $fewStudents, '6 students' => $moreStudents]);

    expect($fewStudents['rows'])->toBe(2)
        ->and($moreStudents['rows'])->toBe(6)
        ->and($moreStudents['queries'])->toBeLessThanOrEqual($fewStudents['queries'] + 2);
});
