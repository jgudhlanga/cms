<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Models\Tenants\Tenant;
use App\Services\Institution\BackfillProgrammeSemestersService;
use App\Services\Institution\RollbackProgrammeSemestersService;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createProgrammeStructureDlc(): DepartmentLevelCourse
{
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'prog-'.Str::lower(Str::random(5)),
        'description' => 'Programme structure tests',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create(['calendar_type' => AcademicCalendarTypeEnum::SEMESTER->value]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    return DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'duration_years' => 1,
        'taught_semester_count' => 2,
        'includes_industrial_attachment' => false,
        'attachment_semester_count' => 0,
    ]);
}

it('generates taught programme semester names from calendar type', function (): void {
    expect(ProgrammeSemesterNameFormatter::taughtName(AcademicCalendarTypeEnum::SEMESTER, 1, 1))
        ->toBe('Year 1 Sem 1')
        ->and(ProgrammeSemesterNameFormatter::taughtName(AcademicCalendarTypeEnum::TERM, 2, 3))
        ->toBe('Year 2 Term 3');
});

it('appends industrial attachment programme semesters after taught phases', function (): void {
    $dlc = createProgrammeStructureDlc();
    $dlc->update([
        'duration_years' => 2,
        'taught_semester_count' => 2,
        'includes_industrial_attachment' => true,
        'attachment_semester_count' => 2,
    ]);

    $synced = app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc->fresh() ?? $dlc);

    expect($synced)->toHaveCount(4)
        ->and($synced->last()?->kind)->toBe(ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT)
        ->and($synced->last()?->name)->toContain('Attachment');
});

it('refuses to delete programme semesters that have student inclusions', function (): void {
    $dlc = createProgrammeStructureDlc();
    $synced = app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc);
    $first = $synced->first();

    $application = createVerifiedStudentApplication('PROG-INCL-01');
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);
    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $statusId = (int) StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    )->id;

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $statusId,
    ]);

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semester->id,
        ],
        [
            'programme_semester_id' => $first?->id,
            'student_enrolment_status_id' => $statusId,
        ],
    );

    $dlc->update(['taught_semester_count' => 1]);
    app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc->fresh() ?? $dlc);

    expect(ProgrammeSemester::query()->whereKey($first?->id)->exists())->toBeTrue();
});

it('dry-runs programme semester backfill without writing', function (): void {
    createProgrammeStructureDlc();

    $before = ProgrammeSemester::query()->count();
    $counts = app(BackfillProgrammeSemestersService::class)->run(dryRun: true);

    expect($counts['dlcs'])->toBeGreaterThan(0)
        ->and(ProgrammeSemester::query()->count())->toBe($before);
});

it('rolls back programme semester backfill from snapshots', function (): void {
    createProgrammeStructureDlc();

    app(BackfillProgrammeSemestersService::class)->run();
    expect(ProgrammeSemester::query()->count())->toBeGreaterThan(0);

    app(RollbackProgrammeSemestersService::class)->run();
    expect(ProgrammeSemester::query()->count())->toBe(0);
});
