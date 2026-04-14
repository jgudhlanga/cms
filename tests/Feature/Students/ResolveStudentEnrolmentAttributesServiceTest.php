<?php

use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\Level;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['Semester 1', 'Semester 2'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Completed'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('resolves the academic calendar that contains the current date', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve(1, 1);

    expect($resolved['academic_calendar_id'])->toBe((int) $calendar->id);
});

it('falls back to the nearest future academic calendar when none contain the current date', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-06-01', config('app.timezone')));

    $future = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve(1, 1);

    expect($resolved['academic_calendar_id'])->toBe((int) $future->id);
});

it('throws when no current or future academic calendar exists', function (): void {
    Carbon::setTestNow(Carbon::parse('2030-01-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);

    $service->resolve(1, 1);
})->throws(StudentEnrolmentResolutionException::class);

it('resolves semester one when the student has no completed enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $semesterOneId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve(99_999, 88_888);

    expect($resolved['academic_year_option_id'])->toBe($semesterOneId);
});

it('resolves semester two when the student has a completed enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $sp1 = createVerifiedStudentProgram('RESOLVER-S1');
    $sp2 = StudentProgram::query()->create([
        'tenant_id' => $sp1->tenant_id,
        'student_id' => $sp1->student_id,
        'institution_department_id' => $sp1->institution_department_id,
        'department_level_id' => $sp1->department_level_id,
        'department_course_id' => $sp1->department_course_id,
        'intake_period_id' => $sp1->intake_period_id,
        'mode_of_study_id' => $sp1->mode_of_study_id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
        'program_status_id' => $sp1->program_status_id,
    ]);

    $completedId = (int) StudentEnrolmentStatus::query()->where('slug', 'completed')->value('id');
    $semesterOneId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $sp1->student_id,
        'student_program_id' => $sp1->id,
        'institution_department_id' => $sp1->institution_department_id,
        'department_level_id' => $sp1->department_level_id,
        'department_course_id' => $sp1->department_course_id,
        'academic_year_option_id' => $semesterOneId,
        'academic_calendar_id' => $calendar->id,
        'student_enrolment_status_id' => $completedId,
    ]);

    $semesterTwoId = (int) AcademicYearOption::query()->where('slug', 'semester-2')->value('id');

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $sp2->student_id, (int) $sp2->id);

    expect($resolved['academic_year_option_id'])->toBe($semesterTwoId);
});

it('resolves semester one when completed enrolment exists in different department combination', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $sp1 = createVerifiedStudentProgram('RESOLVER-DIFF-DEPT-1');
    $differentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $sp1->tenant_id,
        'institution_department_id' => $sp1->institution_department_id,
        'level_id' => Level::factory()->create(['name' => 'Level 2'])->id,
    ]);
    $sp2 = StudentProgram::query()->create([
        'tenant_id' => $sp1->tenant_id,
        'student_id' => $sp1->student_id,
        'institution_department_id' => $sp1->institution_department_id,
        'department_level_id' => $differentLevel->id,
        'department_course_id' => $sp1->department_course_id,
        'intake_period_id' => $sp1->intake_period_id,
        'mode_of_study_id' => $sp1->mode_of_study_id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
        'program_status_id' => $sp1->program_status_id,
    ]);

    $completedId = (int) StudentEnrolmentStatus::query()->where('slug', 'completed')->value('id');
    $semesterOneId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');

    StudentEnrolment::query()->create([
        'student_id' => $sp1->student_id,
        'student_program_id' => $sp1->id,
        'institution_department_id' => $sp1->institution_department_id,
        'department_level_id' => $sp1->department_level_id,
        'department_course_id' => $sp1->department_course_id,
        'academic_year_option_id' => $semesterOneId,
        'academic_calendar_id' => $calendar->id,
        'student_enrolment_status_id' => $completedId,
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $sp2->student_id, (int) $sp2->id);

    expect($resolved['academic_year_option_id'])->toBe($semesterOneId);
});

it('resolves the active student enrolment status id', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve(1, 1);

    expect($resolved['student_enrolment_status_id'])->toBe($activeId);
});
