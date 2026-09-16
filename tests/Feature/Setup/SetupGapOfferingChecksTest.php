<?php

use App\Enums\Setup\SetupGapCheckEnum;
use App\Enums\Shared\TenantEnum;
use App\Jobs\Setup\RecheckSetupGapsJob;
use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ModeOfStudy;
use App\Models\Rbac\Permission;
use App\Models\Setup\SetupGap;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Permission::findOrCreate('manage:online-application-catalogue', 'web');
});

function offeringGap(SetupGapCheckEnum $check, ?int $institutionDepartmentId = null)
{
    return SetupGap::query()
        ->where('check_key', $check->value)
        ->when($institutionDepartmentId, fn ($q) => $q->where('institution_department_id', $institutionDepartmentId))
        ->first();
}

it('stays quiet when the online catalogue matches department setup', function () {
    seedGuestRegistrationProgramme();

    scanSetupGaps();

    expect(offeringGap(SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED))->toBeNull()
        ->and(offeringGap(SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED))->toBeNull()
        ->and(offeringGap(SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD))->toBeNull();
});

it('raises a gap when the catalogue offers a mode the course level is not set up for', function () {
    $seeded = seedGuestRegistrationProgramme();

    $unconfiguredMode = ModeOfStudy::query()->create(['name' => 'Distance '.uniqid()]);
    $offeringCourse = ApplicationOfferingCourse::query()->where('department_course_id', $seeded['courseId'])->firstOrFail();

    ApplicationOfferingMode::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'application_offering_course_id' => $offeringCourse->id,
        'mode_of_study_id' => $unconfiguredMode->id,
    ]);

    scanSetupGaps();

    $gap = offeringGap(SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED, $seeded['departmentId']);

    expect($gap)->not->toBeNull()
        ->and($gap->severity->value)->toBe('critical')
        ->and($gap->meta['modeOfStudyId'] ?? null)->toBe((int) $unconfiguredMode->id)
        ->and($gap->url)->toBe(route('application-offerings.show', ['institution_department' => $seeded['departmentId']]));
});

it('closes the offering-mode gap once the mode is added to department setup', function () {
    $seeded = seedGuestRegistrationProgramme();
    $unconfiguredMode = ModeOfStudy::query()->create(['name' => 'Distance '.uniqid()]);
    $offeringCourse = ApplicationOfferingCourse::query()->where('department_course_id', $seeded['courseId'])->firstOrFail();

    ApplicationOfferingMode::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'application_offering_course_id' => $offeringCourse->id,
        'mode_of_study_id' => $unconfiguredMode->id,
    ]);

    scanSetupGaps();
    expect(offeringGap(SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED, $seeded['departmentId']))->not->toBeNull();

    $row = DB::table('course_level_modes')
        ->where('department_course_id', $seeded['courseId'])
        ->where('department_level_id', $seeded['departmentLevelId'])
        ->first();
    $modes = json_decode((string) $row->modes, true);
    $modes[] = (int) $unconfiguredMode->id;
    DB::table('course_level_modes')->where('id', $row->id)->update(['modes' => json_encode($modes)]);

    scanSetupGaps();

    expect(offeringGap(SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED, $seeded['departmentId'])->resolved_at)->not->toBeNull();
});

it('raises a gap when the catalogue offers a course/level department setup has unlinked', function () {
    $seeded = seedGuestRegistrationProgramme();

    DB::table('department_level_courses')
        ->where('department_course_id', $seeded['courseId'])
        ->where('department_level_id', $seeded['departmentLevelId'])
        ->delete();

    scanSetupGaps();

    $gap = offeringGap(SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED, $seeded['departmentId']);

    expect($gap)->not->toBeNull()
        ->and($gap->meta['departmentCourseId'] ?? null)->toBe((int) $seeded['courseId']);

    // The course and level themselves are still there — this is unlinking, not deletion.
    expect(offeringGap(SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD, $seeded['departmentId']))->toBeNull();
});

it('raises a gap when the catalogue offers a course department setup deleted, not the unlinked check', function () {
    $seeded = seedGuestRegistrationProgramme();

    DepartmentCourse::query()->whereKey($seeded['courseId'])->first()->delete();

    scanSetupGaps();

    $gap = offeringGap(SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD, $seeded['departmentId']);

    expect($gap)->not->toBeNull()
        ->and($gap->meta['departmentCourseId'] ?? null)->toBe((int) $seeded['courseId']);

    // Deletion, not mere unlinking — the other check must stay quiet so the same offering is not
    // reported twice under two different problems.
    expect(offeringGap(SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED, $seeded['departmentId']))->toBeNull();
});

it('raises a gap when the catalogue offers a level department setup deleted', function () {
    $seeded = seedGuestRegistrationProgramme();

    DepartmentLevel::query()->whereKey($seeded['departmentLevelId'])->first()->delete();

    scanSetupGaps();

    expect(offeringGap(SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD, $seeded['departmentId']))->not->toBeNull();
});

it('hides offering gaps from a user who cannot manage the online application catalogue', function () {
    $seeded = seedGuestRegistrationProgramme();
    DepartmentCourse::query()->whereKey($seeded['courseId'])->first()->delete();
    scanSetupGaps();

    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);

    $this->actingAs($user);
    $response = $this->getJson(route('setup-gaps.index'));
    $response->assertOk();

    expect(collect($response->json('gaps'))->pluck('check'))
        ->not->toContain(SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD->value);
});

it('shows offering gaps to a user who manages the online application catalogue', function () {
    $seeded = seedGuestRegistrationProgramme();
    DepartmentCourse::query()->whereKey($seeded['courseId'])->first()->delete();
    scanSetupGaps();

    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    $user->givePermissionTo('manage:online-application-catalogue');

    $this->actingAs($user);
    $response = $this->getJson(route('setup-gaps.index'));
    $response->assertOk();

    expect(collect($response->json('gaps'))->pluck('check'))
        ->toContain(SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD->value);
});

it('queues a re-check when the catalogue is edited', function () {
    Queue::fake();

    $seeded = seedGuestRegistrationProgramme();
    $unconfiguredMode = ModeOfStudy::query()->create(['name' => 'Distance '.uniqid()]);
    $offeringCourse = ApplicationOfferingCourse::query()->where('department_course_id', $seeded['courseId'])->firstOrFail();

    ApplicationOfferingMode::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'application_offering_course_id' => $offeringCourse->id,
        'mode_of_study_id' => $unconfiguredMode->id,
    ]);

    Queue::assertPushed(RecheckSetupGapsJob::class, function (RecheckSetupGapsJob $job): bool {
        return in_array(SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED->value, $job->checkKeys, true);
    });
});
