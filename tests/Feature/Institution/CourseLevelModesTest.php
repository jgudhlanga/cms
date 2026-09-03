<?php

declare(strict_types=1);

use App\DTO\Institution\DepartmentCourseUpdateDto;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

function courseLevelModesUser(): User
{
    $user = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $user->givePermissionTo('view:department-metadata');
    $user->givePermissionTo('update:department-metadata');
    test()->actingAs($user);

    return $user;
}

function spareLevelOnCourse(StudentApplication $application): DepartmentLevel
{
    $level = Level::factory()->create(['name' => 'Spare '.Str::upper(Str::random(5))]);

    return DepartmentLevel::query()->create([
        'tenant_id' => Tenant::query()->firstOrFail()->id,
        'institution_department_id' => $application->institution_department_id,
        'level_id' => $level->id,
    ]);
}

function linkLevelToCourse(StudentApplication $application, DepartmentLevel $departmentLevel): DepartmentLevelCourse
{
    return DepartmentLevelCourse::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $departmentLevel->id,
    ]);
}

function blockReleaseMode(): ModeOfStudy
{
    return ModeOfStudy::query()->firstOrCreate(
        ['name' => 'Block Release'],
        ['description' => 'Block Release'],
    );
}

it('hides leftover modes that belong to an unlinked level', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-HIDE');
    $orphanLevel = spareLevelOnCourse($application);
    $blockRelease = blockReleaseMode();

    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'modes' => [(int) $application->mode_of_study_id],
    ]);
    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $orphanLevel->id,
        'modes' => [(int) $application->mode_of_study_id, (int) $blockRelease->id],
    ]);

    courseLevelModesUser();
    Sanctum::actingAs(auth()->user());

    $response = $this->getJson(route('v1.department-metadata.courses', $application->institution_department_id));
    $response->assertOk();

    $course = collect($response->json('courses'))->firstWhere('id', $application->department_course_id);
    $modeIds = collect($course['relationships']['modes'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($modeIds)->toContain((int) $application->mode_of_study_id)
        ->and($modeIds)->not->toContain((int) $blockRelease->id);

    $this->get(route('department-courses.modes', $application->department_course_id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/departments/courses/CourseLevelModes')
            ->has('courseLevelModes', 1)
            ->where('courseLevelModes.0.attributes.departmentLevelId', (int) $application->department_level_id)
        );
});

it('blocks unlinking a course level that still has an application', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-UNLINK-APP');
    $spare = spareLevelOnCourse($application);
    linkLevelToCourse($application, $spare);
    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'modes' => [(int) $application->mode_of_study_id],
    ]);

    courseLevelModesUser();

    $this->from(route('department-courses.show', $application->department_course_id))
        ->post(route('department-courses.update', $application->department_course_id), [
            'department_level_ids' => [$spare->id],
        ])
        ->assertSessionHasErrors('department_level_ids');

    $application->loadMissing(['departmentCourse.course', 'departmentLevel.level']);
    $courseName = (string) $application->departmentCourse?->course?->name;
    $levelName = (string) $application->departmentLevel?->level?->name;

    expect(session('errors')->first('department_level_ids'))
        ->toContain('Cannot unlink '.$levelName.' from '.$courseName)
        ->toContain('1 application')
        ->not->toContain('enrolment');

    expect(DepartmentLevelCourse::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->exists())->toBeTrue()
        ->and(CourseLevelMode::query()
            ->where('department_course_id', $application->department_course_id)
            ->where('department_level_id', $application->department_level_id)
            ->exists())->toBeTrue();
});

it('unlinks an unused course level and deletes its modes', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-UNLINK-FREE');
    $spare = spareLevelOnCourse($application);
    linkLevelToCourse($application, $spare);
    $blockRelease = blockReleaseMode();

    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $spare->id,
        'modes' => [(int) $blockRelease->id],
    ]);

    courseLevelModesUser();

    $this->post(route('department-courses.update', $application->department_course_id), [
        'department_level_ids' => [$application->department_level_id],
    ])->assertSuccessful();

    expect(DepartmentLevelCourse::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $spare->id)
        ->exists())->toBeFalse()
        ->and(CourseLevelMode::query()
            ->where('department_course_id', $application->department_course_id)
            ->where('department_level_id', $spare->id)
            ->exists())->toBeFalse();
});

it('does not remove a mode that an application still uses', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-KEEP-MODE');
    $fullTime = ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);
    $blockRelease = blockReleaseMode();
    $application->update(['mode_of_study_id' => $blockRelease->id]);

    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'modes' => [(int) $fullTime->id, (int) $blockRelease->id],
    ]);

    courseLevelModesUser();

    $this->from(route('department-courses.modes', $application->department_course_id))
        ->post(route('department-courses.modes.store', $application->department_course_id), [
            'department_course_id' => $application->department_course_id,
            'mode_ids' => [
                (string) $application->department_level_id => [$fullTime->id],
            ],
        ])
        ->assertSessionHasErrors('mode_ids');

    $application->loadMissing(['departmentCourse.course', 'departmentLevel.level']);
    $courseName = (string) $application->departmentCourse?->course?->name;
    $levelName = (string) $application->departmentLevel?->level?->name;

    expect(session('errors')->first('mode_ids'))
        ->toContain('Cannot remove Block Release from '.$courseName.' ('.$levelName.')')
        ->toContain('1 application')
        ->not->toContain('enrolment');

    $row = CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->first();

    expect($row)->not->toBeNull()
        ->and(collect($row->modes)->map(fn ($id) => (int) $id)->all())->toContain((int) $blockRelease->id);
});

it('saves selected modes and prunes leftover unused level rows', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-PRUNE');
    $orphanLevel = spareLevelOnCourse($application);
    $blockRelease = blockReleaseMode();
    $fullTime = ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);

    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'modes' => [(int) $fullTime->id, (int) $blockRelease->id],
    ]);
    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $orphanLevel->id,
        'modes' => [(int) $blockRelease->id],
    ]);

    courseLevelModesUser();

    $this->post(route('department-courses.modes.store', $application->department_course_id), [
        'department_course_id' => $application->department_course_id,
        'mode_ids' => [
            (string) $application->department_level_id => [$fullTime->id],
        ],
    ])->assertSuccessful();

    $linked = CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $application->department_level_id)
        ->first();

    expect(collect($linked?->modes)->map(fn ($id) => (int) $id)->all())->toBe([(int) $fullTime->id])
        ->and(CourseLevelMode::query()
            ->where('department_course_id', $application->department_course_id)
            ->where('department_level_id', $orphanLevel->id)
            ->exists())->toBeFalse();
});

it('does not prune a leftover level that still has an enrolment', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-ENROL-ORPHAN');
    $orphanLevel = spareLevelOnCourse($application);
    $blockRelease = blockReleaseMode();

    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $orphanLevel->id,
        'modes' => [(int) $blockRelease->id],
    ]);

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $orphanLevel->id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $blockRelease->id,
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

    $fullTime = ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);
    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $application->department_level_id,
        'modes' => [(int) $fullTime->id],
    ]);

    courseLevelModesUser();

    $this->post(route('department-courses.modes.store', $application->department_course_id), [
        'department_course_id' => $application->department_course_id,
        'mode_ids' => [
            (string) $application->department_level_id => [$fullTime->id],
        ],
    ])->assertSuccessful();

    expect(CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $orphanLevel->id)
        ->exists())->toBeTrue();
});

it('repairs leftover modes by pruning unused rows and restoring in-use levels', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-REPAIR');
    $unusedLevel = spareLevelOnCourse($application);
    $usedLevel = spareLevelOnCourse($application);
    $blockRelease = blockReleaseMode();
    $fullTime = ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);

    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $unusedLevel->id,
        'modes' => [(int) $blockRelease->id],
    ]);
    CourseLevelMode::query()->create([
        'department_course_id' => $application->department_course_id,
        'department_level_id' => $usedLevel->id,
        'modes' => [(int) $fullTime->id, (int) $blockRelease->id],
    ]);

    StudentApplication::query()->whereKey($application->id)->update([
        'department_level_id' => $usedLevel->id,
        'mode_of_study_id' => $fullTime->id,
    ]);

    $this->artisan('maintenance:repair-orphan-course-level-modes')
        ->assertSuccessful();

    expect(CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $unusedLevel->id)
        ->exists())->toBeTrue()
        ->and(DepartmentLevelCourse::query()
            ->where('department_course_id', $application->department_course_id)
            ->where('department_level_id', $usedLevel->id)
            ->exists())->toBeFalse();

    $this->artisan('maintenance:repair-orphan-course-level-modes', ['--execute' => true])
        ->assertSuccessful();

    expect(CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $unusedLevel->id)
        ->exists())->toBeFalse()
        ->and(DepartmentLevelCourse::query()
            ->where('department_course_id', $application->department_course_id)
            ->where('department_level_id', $usedLevel->id)
            ->exists())->toBeTrue();

    $restored = CourseLevelMode::query()
        ->where('department_course_id', $application->department_course_id)
        ->where('department_level_id', $usedLevel->id)
        ->first();

    expect(collect($restored?->modes)->map(fn ($id) => (int) $id)->all())->toBe([(int) $fullTime->id]);
});

it('counts enrolments when deciding whether a course level is still in use', function (): void {
    $application = createVerifiedStudentApplication('STU-CLM-ENROL-USE');
    $spare = spareLevelOnCourse($application);
    linkLevelToCourse($application, $spare);

    StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $spare->id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'semester_id' => Semester::query()->firstOrCreate(
            ['slug' => 'semester-1'],
            ['name' => 'Semester 1', 'description' => null],
        )->id,
        'academic_calendar_id' => AcademicCalendar::query()->create([
            'calendar_year' => '2027',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'opening_date' => now()->subDays(30)->toDateString(),
            'closing_date' => now()->addMonths(6)->toDateString(),
        ])->id,
        'student_enrolment_status_id' => StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => 'Active'],
            ['description' => 'Test'],
        )->id,
    ]);

    expect(fn () => app(IDepartmentCourseRepository::class)->update(
        $application->departmentCourse,
        new DepartmentCourseUpdateDto(department_level_ids: [(int) $application->department_level_id]),
    ))->toThrow(function (ValidationException $exception) use ($application, $spare): void {
        $spare->loadMissing('level');
        $application->loadMissing('departmentCourse.course');
        $message = $exception->errors()['department_level_ids'][0] ?? '';

        expect($message)
            ->toContain('Cannot unlink '.(string) $spare->level?->name.' from '.(string) $application->departmentCourse?->course?->name)
            ->toContain('1 enrolment')
            ->not->toContain('application');
    });
});
