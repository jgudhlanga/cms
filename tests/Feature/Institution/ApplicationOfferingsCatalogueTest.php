<?php

declare(strict_types=1);

use App\Enums\Shared\TenantEnum;
use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Applications\ApplicationOfferingLevel;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use App\Services\Applications\ApplicationOfferingBackfillService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Permission::findOrCreate('manage:online-application-catalogue', 'web');
});

function makeEnrolmentsManager(): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    $user->givePermissionTo('manage:online-application-catalogue');

    return $user;
}

test('enrolments catalogue index requires manage:online-application-catalogue', function () {
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);

    $this->actingAs($user)
        ->get(route('application-offerings.index'))
        ->assertForbidden();
});

test('enrolments catalogue sync creates offering tree', function () {
    $user = makeEnrolmentsManager();
    $seeded = seedGuestRegistrationProgramme();
    $institutionDepartment = InstitutionDepartment::query()->findOrFail($seeded['departmentId']);

    ApplicationOfferingDepartment::query()
        ->where('institution_department_id', $institutionDepartment->id)
        ->with('levels.courses.modes')
        ->get()
        ->each(function (ApplicationOfferingDepartment $offering): void {
            foreach ($offering->levels as $level) {
                foreach ($level->courses as $course) {
                    $course->modes()->delete();
                    $course->delete();
                }
                $level->delete();
            }
            $offering->delete();
        });

    $this->actingAs($user)
        ->from(route('application-offerings.show', $institutionDepartment->id))
        ->put(route('application-offerings.update', $institutionDepartment->id), [
            'enabled' => true,
            'has_apprentice_programmes' => true,
            'levels' => [
                [
                    'department_level_id' => $seeded['departmentLevelId'],
                    'courses' => [
                        [
                            'department_course_id' => $seeded['courseId'],
                            'mode_of_study_ids' => [$seeded['modeId']],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('application-offerings.show', $institutionDepartment->id));

    $offering = ApplicationOfferingDepartment::query()
        ->where('institution_department_id', $institutionDepartment->id)
        ->first();

    expect($offering)->not->toBeNull()
        ->and($offering->has_apprentice_programmes)->toBeTrue();

    expect(ApplicationOfferingLevel::query()->where('department_level_id', $seeded['departmentLevelId'])->exists())->toBeTrue();
    expect(ApplicationOfferingCourse::query()->where('department_course_id', $seeded['courseId'])->exists())->toBeTrue();
    expect(ApplicationOfferingMode::query()->where('mode_of_study_id', $seeded['modeId'])->exists())->toBeTrue();
});

test('enrolments catalogue sync rejects course without modes', function () {
    $user = makeEnrolmentsManager();
    $seeded = seedGuestRegistrationProgramme();

    $this->actingAs($user)
        ->from(route('application-offerings.show', $seeded['departmentId']))
        ->put(route('application-offerings.update', $seeded['departmentId']), [
            'enabled' => true,
            'has_apprentice_programmes' => false,
            'levels' => [
                [
                    'department_level_id' => $seeded['departmentLevelId'],
                    'courses' => [
                        [
                            'department_course_id' => $seeded['courseId'],
                            'mode_of_study_ids' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertSessionHasErrors();
});

test('backfill and restore commands fail after legacy flag columns are dropped', function () {
    if (
        Schema::hasColumn('department_levels', 'show_on_current_application_period')
        || Schema::hasColumn('department_courses', 'show_on_current_application_period')
        || Schema::hasColumn('institution_departments', 'has_apprentice_courses')
    ) {
        $this->markTestSkipped('Legacy flag columns still present; run drop migration first.');
    }

    $service = app(ApplicationOfferingBackfillService::class);

    expect(fn () => $service->backfill(dryRun: true))
        ->toThrow(RuntimeException::class);

    expect(fn () => $service->restoreFlagsFromOfferings(dryRun: true))
        ->toThrow(RuntimeException::class);
});
