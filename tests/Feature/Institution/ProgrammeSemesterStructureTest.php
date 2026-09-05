<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\DTO\Institution\DepartmentCourseUpdateDto;
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
use App\Models\Users\User;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
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
        ->toBe('Year 2 Term 3')
        ->and(ProgrammeSemesterNameFormatter::taughtName(AcademicCalendarTypeEnum::ABMA, 1, 1))
        ->toBe('Year 1 ABMA 1');
});

it('creates department level courses with abma calendar defaults and programme semesters', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'prog-'.Str::lower(Str::random(5)),
        'description' => 'ABMA programme structure tests',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create([
        'name' => 'ABMA Level 3',
        'calendar_type' => AcademicCalendarTypeEnum::ABMA->value,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    app(IDepartmentCourseRepository::class)->update(
        $departmentCourse,
        new DepartmentCourseUpdateDto(department_level_ids: [$departmentLevel->id]),
    );

    $dlc = DepartmentLevelCourse::query()
        ->where('department_course_id', $departmentCourse->id)
        ->first();

    expect($dlc)->not->toBeNull()
        ->and($dlc->taught_semester_count)->toBe(4)
        ->and($dlc->programmeSemesters)->toHaveCount(4)
        ->and($dlc->programmeSemesters->pluck('name')->all())->toBe([
            'Year 1 ABMA 1',
            'Year 1 ABMA 2',
            'Year 1 ABMA 3',
            'Year 1 ABMA 4',
        ]);
});

it('creates department level courses with term calendar defaults and programme semesters', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'prog-'.Str::lower(Str::random(5)),
        'description' => 'Term programme structure tests',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create(['calendar_type' => AcademicCalendarTypeEnum::TERM->value]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    app(IDepartmentCourseRepository::class)->update(
        $departmentCourse,
        new DepartmentCourseUpdateDto(department_level_ids: [$departmentLevel->id]),
    );

    $dlc = DepartmentLevelCourse::query()
        ->where('department_course_id', $departmentCourse->id)
        ->first();

    expect($dlc)->not->toBeNull()
        ->and($dlc->taught_semester_count)->toBe(3)
        ->and($dlc->programmeSemesters)->toHaveCount(3)
        ->and($dlc->programmeSemesters->pluck('name')->all())->toBe([
            'Year 1 Term 1',
            'Year 1 Term 2',
            'Year 1 Term 3',
        ]);
});

it('persists a year-and-a-half programme with three taught semesters', function (): void {
    $dlc = createProgrammeStructureDlc();
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('manage:programme-structures');

    $this->actingAs($user)
        ->from(route('institution-departments.show', $dlc->departmentLevel->institution_department_id))
        ->post(route('department-level-courses.programme-structure.update', $dlc), [
            'duration_years' => 1.5,
            'taught_semester_count' => 3,
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $dlc->refresh()->load('programmeSemesters');

    expect((float) $dlc->duration_years)->toBe(1.5)
        ->and($dlc->taught_semester_count)->toBe(3)
        ->and($dlc->programmeSemesters)->toHaveCount(3)
        ->and($dlc->programmeSemesters->pluck('name')->all())->toBe([
            'Year 1 Sem 1',
            'Year 1 Sem 2',
            'Year 2 Sem 1',
        ]);
});

it('rejects programme duration below half a year', function (): void {
    $dlc = createProgrammeStructureDlc();
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('manage:programme-structures');

    $this->actingAs($user)
        ->from(route('institution-departments.show', $dlc->departmentLevel->institution_department_id))
        ->post(route('department-level-courses.programme-structure.update', $dlc), [
            'duration_years' => 0.25,
            'taught_semester_count' => 3,
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ])
        ->assertSessionHasErrors('duration_years');

    expect((int) $dlc->fresh()->taught_semester_count)->toBe(2);
});

it('includes industrial attachment in duration years without changing taught or attachment counts', function (): void {
    $dlc = createProgrammeStructureDlc();
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('manage:programme-structures');

    $this->actingAs($user)
        ->from(route('institution-departments.show', $dlc->departmentLevel->institution_department_id))
        ->post(route('department-level-courses.programme-structure.update', $dlc), [
            'duration_years' => 1,
            'taught_semester_count' => 2,
            'includes_industrial_attachment' => true,
            'attachment_semester_count' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $dlc->refresh()->load('programmeSemesters');

    expect((float) $dlc->duration_years)->toBe(2.0)
        ->and($dlc->taught_semester_count)->toBe(2)
        ->and($dlc->includes_industrial_attachment)->toBeTrue()
        ->and($dlc->attachment_semester_count)->toBe(2)
        ->and($dlc->programmeSemesters)->toHaveCount(4)
        ->and($dlc->programmeSemesters->pluck('name')->all())->toBe([
            'Year 1 Sem 1',
            'Year 1 Sem 2',
            'Year 2 Attachment 1',
            'Year 2 Attachment 2',
        ]);
});

it('adds a one-year attachment onto a year-and-a-half taught programme', function (): void {
    $dlc = createProgrammeStructureDlc();
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('manage:programme-structures');

    $this->actingAs($user)
        ->from(route('institution-departments.show', $dlc->departmentLevel->institution_department_id))
        ->post(route('department-level-courses.programme-structure.update', $dlc), [
            'duration_years' => 1.5,
            'taught_semester_count' => 3,
            'includes_industrial_attachment' => true,
            'attachment_semester_count' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $dlc->refresh();

    expect((float) $dlc->duration_years)->toBe(2.5)
        ->and($dlc->taught_semester_count)->toBe(3)
        ->and($dlc->attachment_semester_count)->toBe(2);
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
