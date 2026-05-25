<?php

use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['Semester 1', 'Semester 2', 'Term 1', 'Term 2', 'Term 3', 'Term 4'] as $name) {
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
    $studentProgram = createResolverStudentProgram('RESOLVE-CALENDAR-CURRENT', 'semester', '2025/2026');

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'term',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $studentProgram->student_id, (int) $studentProgram->id);

    expect($resolved['academic_calendar_id'])->toBe((int) $calendar->id);
});

it('falls back to the nearest future academic calendar when none contain the current date', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-06-01', config('app.timezone')));
    $studentProgram = createResolverStudentProgram('RESOLVE-CALENDAR-FUTURE', 'semester', '2025/2026');

    $future = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $studentProgram->student_id, (int) $studentProgram->id);

    expect($resolved['academic_calendar_id'])->toBe((int) $future->id);
});

it('throws when no academic calendar exists for the student program year and type', function (): void {
    Carbon::setTestNow(Carbon::parse('2030-01-01', config('app.timezone')));
    $studentProgram = createResolverStudentProgram('RESOLVE-CALENDAR-MISS', 'semester', '2027/2028');

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $service = app(ResolveStudentEnrolmentAttributesService::class);

    $service->resolve((int) $studentProgram->student_id, (int) $studentProgram->id);
})->throws(StudentEnrolmentResolutionException::class);

it('throws when the student program does not exist', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $service = app(ResolveStudentEnrolmentAttributesService::class);

    $service->resolve(1, 999_999);
})->throws(StudentEnrolmentResolutionException::class);

it('resolves semester one when the student has no completed enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));
    $studentProgram = createResolverStudentProgram('RESOLVE-SEM-ONE', 'semester', '2025/2026');

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $semesterOneId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $studentProgram->student_id, (int) $studentProgram->id);

    expect($resolved['academic_year_option_id'])->toBe($semesterOneId);
});

it('resolves semester two when the student has a completed enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $sp1 = createResolverStudentProgram('RESOLVER-S1', 'semester', '2025/2026');
    $sp2 = createSiblingProgram($sp1);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
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
        'mode_of_study_id' => $sp1->mode_of_study_id,
        'student_enrolment_status_id' => $completedId,
    ]);

    $semesterTwoId = (int) AcademicYearOption::query()->where('slug', 'semester-2')->value('id');

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $sp2->student_id, (int) $sp2->id);

    expect($resolved['academic_year_option_id'])->toBe($semesterTwoId);
});

it('caps term progression at the last available term option', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $sp = createResolverStudentProgram('RESOLVER-TERM-CAP', 'term', '2025/2026');

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'term',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $completedId = (int) StudentEnrolmentStatus::query()->where('slug', 'completed')->value('id');
    $termOneId = (int) AcademicYearOption::query()->where('slug', 'term-1')->value('id');
    $termFourId = (int) AcademicYearOption::query()->where('slug', 'term-4')->value('id');

    for ($index = 0; $index < 5; $index++) {
        StudentEnrolment::query()->create([
            'student_id' => $sp->student_id,
            'student_program_id' => $sp->id,
            'institution_department_id' => $sp->institution_department_id,
            'department_level_id' => $sp->department_level_id,
            'department_course_id' => $sp->department_course_id,
            'academic_year_option_id' => $termOneId,
            'academic_calendar_id' => $calendar->id,
            'mode_of_study_id' => $sp->mode_of_study_id,
            'student_enrolment_status_id' => $completedId,
        ]);
    }

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $sp->student_id, (int) $sp->id);

    expect($resolved['academic_year_option_id'])->toBe($termFourId);
});

it('resolves the active student enrolment status id', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));
    $studentProgram = createResolverStudentProgram('RESOLVE-STATUS', 'semester', '2025/2026');

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $service = app(ResolveStudentEnrolmentAttributesService::class);
    $resolved = $service->resolve((int) $studentProgram->student_id, (int) $studentProgram->id);

    expect($resolved['student_enrolment_status_id'])->toBe($activeId);
});

function createResolverStudentProgram(string $studentNumber, string $calendarType, string $calendarYear): StudentProgram
{
    $studentProgram = createVerifiedStudentProgram($studentNumber);

    $studentProgram->intakePeriod()->update([
        'calendar_year' => $calendarYear,
    ]);

    $studentProgram->departmentLevel->level->update([
        'calendar_type' => $calendarType,
    ]);

    return $studentProgram->fresh(['departmentLevel.level', 'intakePeriod']);
}

function createSiblingProgram(StudentProgram $studentProgram): StudentProgram
{
    return StudentProgram::query()->create([
        'tenant_id' => $studentProgram->tenant_id,
        'student_id' => $studentProgram->student_id,
        'institution_department_id' => $studentProgram->institution_department_id,
        'department_level_id' => $studentProgram->department_level_id,
        'department_course_id' => $studentProgram->department_course_id,
        'intake_period_id' => $studentProgram->intake_period_id,
        'mode_of_study_id' => $studentProgram->mode_of_study_id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
        'program_status_id' => $studentProgram->program_status_id,
    ]);
}
