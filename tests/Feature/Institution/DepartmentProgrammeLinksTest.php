<?php

declare(strict_types=1);

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Institution\ProgrammeLinkUsageGuard;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function departmentMetaDataUser(): User
{
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('create:department-metadata');
    $user->givePermissionTo('delete:department-metadata');
    test()->actingAs($user);

    return $user;
}

/**
 * A second department level on the same department, unused by any application.
 */
function spareDepartmentLevel(int $institutionDepartmentId): DepartmentLevel
{
    $level = Level::factory()->create(['name' => 'Spare '.Str::upper(Str::random(5))]);

    return DepartmentLevel::query()->create([
        'tenant_id' => Tenant::query()->firstOrFail()->id,
        'institution_department_id' => $institutionDepartmentId,
        'level_id' => $level->id,
    ]);
}

it('restores a trashed department level instead of creating a new id', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-RESTORE');
    $departmentLevel = $application->departmentLevel;
    $departmentLevel->delete();
    departmentMetaDataUser();

    $this->post(route('department-levels.sync', $application->institution_department_id), [
        'level_ids' => [$departmentLevel->level_id],
    ])->assertSuccessful();

    $links = DepartmentLevel::query()
        ->withTrashed()
        ->where('institution_department_id', $application->institution_department_id)
        ->where('level_id', $departmentLevel->level_id)
        ->get();

    expect($links)->toHaveCount(1)
        ->and($links->first()->id)->toBe($departmentLevel->id)
        ->and($links->first()->deleted_at)->toBeNull();
});

it('blocks unlinking a department level still used by an application', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-BLOCK');
    $departmentLevel = $application->departmentLevel;
    departmentMetaDataUser();

    $this->from(route('institution-departments.show', $application->institution_department_id))
        ->post(route('department-levels.sync', $application->institution_department_id), [
            'level_ids' => [],
        ])
        ->assertSessionHasErrors('level_ids');

    expect($departmentLevel->fresh()->deleted_at)->toBeNull();
});

it('keeps in use links when the payload arrives empty', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-EMPTY');
    $spare = spareDepartmentLevel((int) $application->institution_department_id);
    departmentMetaDataUser();

    $this->post(route('department-levels.sync', $application->institution_department_id), [])
        ->assertSessionHasErrors('level_ids');

    // The whole sync is one transaction, so the unused level survives too.
    expect($application->departmentLevel->fresh()->deleted_at)->toBeNull()
        ->and($spare->fresh()->deleted_at)->toBeNull();
});

it('soft deletes a department level that no application uses', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-UNUSED');
    $spare = spareDepartmentLevel((int) $application->institution_department_id);
    departmentMetaDataUser();

    $this->post(route('department-levels.sync', $application->institution_department_id), [
        'level_ids' => [$application->departmentLevel->level_id],
    ])->assertSuccessful();

    expect($spare->fresh()->deleted_at)->not->toBeNull()
        ->and($application->departmentLevel->fresh()->deleted_at)->toBeNull();
});

it('reports usage for a level an application still points at', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-USAGE');
    $spare = spareDepartmentLevel((int) $application->institution_department_id);
    $guard = app(ProgrammeLinkUsageGuard::class);

    expect($guard->levelUsage([(int) $application->department_level_id]))
        ->toBe([(int) $application->department_level_id => 1])
        ->and($guard->levelUsage([(int) $spare->id]))->toBe([]);

    $guard->assertLevelsUnused([(int) $spare->id]);

    expect(fn () => $guard->assertLevelsUnused([(int) $application->department_level_id]))
        ->toThrow(ValidationException::class);
});

it('restores a trashed department course instead of creating a new id', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-COURSE');
    $departmentCourse = $application->departmentCourse;
    $departmentCourse->delete();
    departmentMetaDataUser();

    $this->post(route('department-courses.sync', $application->institution_department_id), [
        'course_ids' => [$departmentCourse->course_id],
    ])->assertSuccessful();

    $links = DepartmentCourse::query()
        ->withTrashed()
        ->where('institution_department_id', $application->institution_department_id)
        ->where('course_id', $departmentCourse->course_id)
        ->get();

    expect($links)->toHaveCount(1)
        ->and($links->first()->id)->toBe($departmentCourse->id)
        ->and($links->first()->deleted_at)->toBeNull();
});

it('blocks unlinking a department course still used by an application', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-COURSE-BLOCK');
    $departmentCourse = $application->departmentCourse;
    departmentMetaDataUser();

    $this->from(route('institution-departments.show', $application->institution_department_id))
        ->post(route('department-courses.sync', $application->institution_department_id), [
            'course_ids' => [],
        ])
        ->assertSessionHasErrors('course_ids');

    expect($departmentCourse->fresh()->deleted_at)->toBeNull();
});

it('does not unlink the same course in another department', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $course = Course::factory()->create();

    $otherDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => Department::factory()->create()->id,
        'department_code' => 'oth-'.Str::lower(Str::random(6)),
    ]);
    $otherLink = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $otherDepartment->id,
        'course_id' => $course->id,
    ]);

    $subject = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => Department::factory()->create()->id,
        'department_code' => 'sub-'.Str::lower(Str::random(6)),
    ]);
    $subjectLink = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $subject->id,
        'course_id' => $course->id,
    ]);

    departmentMetaDataUser();

    $this->post(route('department-courses.sync', $subject->id), [
        'course_ids' => [],
    ])->assertSuccessful();

    expect($subjectLink->fresh()->deleted_at)->not->toBeNull()
        ->and($otherLink->fresh()->deleted_at)->toBeNull();
});

it('counts enrolments as usage even when the application moved on', function (): void {
    $application = createVerifiedStudentApplication('STU-LINK-ENROL');
    $departmentLevel = $application->departmentLevel;
    $spare = spareDepartmentLevel((int) $application->institution_department_id);

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'semester_id' => Semester::query()->firstOrCreate(
            ['slug' => 'semester-1'],
            ['name' => 'Semester 1', 'description' => null],
        )->id,
        'academic_calendar_id' => AcademicCalendar::query()->create([
            'calendar_year' => '2026',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'opening_date' => now()->subDays(30)->toDateString(),
            'closing_date' => now()->addMonths(6)->toDateString(),
        ])->id,
        'student_enrolment_status_id' => StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => 'Active'],
            ['description' => 'Test'],
        )->id,
    ]);

    StudentApplication::query()->whereKey($application->id)->update([
        'department_level_id' => $spare->id,
    ]);

    departmentMetaDataUser();

    $this->from(route('institution-departments.show', $application->institution_department_id))
        ->post(route('department-levels.sync', $application->institution_department_id), [
            'level_ids' => [$spare->level_id],
        ])
        ->assertSessionHasErrors('level_ids');

    expect($departmentLevel->fresh()->deleted_at)->toBeNull();
});
