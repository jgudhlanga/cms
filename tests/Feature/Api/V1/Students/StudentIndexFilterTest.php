<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('filters students by institution department id array', function (): void {
    $program = createVerifiedStudentProgram('STU-IDX-'.strtoupper(Str::random(4)));

    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    Sanctum::actingAs($user);

    $academicYearOption = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'api-filter-year'],
        ['name' => 'Semester 1', 'description' => null],
    );

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active-api-filter'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    StudentEnrolment::query()->create([
        'student_id' => $program->student_id,
        'student_program_id' => $program->id,
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $program->mode_of_study_id,
        'student_enrolment_status_id' => $status->id,
    ]);

    $deptId = (int) $program->institution_department_id;

    $matched = $this->getJson(route('v1.students.index').'?department[]='.$deptId);
    $matched->assertOk();
    $ids = collect($matched->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($ids)->toContain((int) $program->student_id);

    $empty = $this->getJson(route('v1.students.index').'?department[]=999999999');
    $empty->assertOk();
    expect($empty->json('data'))->toBe([]);
});
