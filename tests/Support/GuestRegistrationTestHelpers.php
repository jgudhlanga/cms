<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Applications\ApplicationOfferingLevel;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\Course;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Services\Students\RegistrationIntentSession;

/**
 * @param  list<int>  $modeIds
 */
function seedApplicationOffering(
    InstitutionDepartment $institutionDepartment,
    DepartmentLevel $departmentLevel,
    DepartmentCourse $departmentCourse,
    array $modeIds,
    bool $hasApprenticeProgrammes = false,
): void {
    $tenantId = (int) $institutionDepartment->tenant_id;

    $offeringDepartment = ApplicationOfferingDepartment::query()->updateOrCreate(
        [
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
        ],
        ['has_apprentice_programmes' => $hasApprenticeProgrammes],
    );

    $offeringLevel = ApplicationOfferingLevel::query()->updateOrCreate(
        [
            'application_offering_department_id' => $offeringDepartment->id,
            'department_level_id' => $departmentLevel->id,
        ],
        ['tenant_id' => $tenantId],
    );

    $offeringCourse = ApplicationOfferingCourse::query()->updateOrCreate(
        [
            'application_offering_level_id' => $offeringLevel->id,
            'department_course_id' => $departmentCourse->id,
        ],
        ['tenant_id' => $tenantId],
    );

    ApplicationOfferingMode::query()
        ->where('application_offering_course_id', $offeringCourse->id)
        ->delete();

    foreach ($modeIds as $modeId) {
        ApplicationOfferingMode::query()->updateOrCreate(
            [
                'application_offering_course_id' => $offeringCourse->id,
                'mode_of_study_id' => (int) $modeId,
            ],
            ['tenant_id' => $tenantId],
        );
    }
}

/**
 * @return array{level: Level, intakeId: int, departmentId: int, departmentLevelId: int, courseId: int, modeId: int}
 */
function seedGuestRegistrationProgramme(?Level $level = null): array
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    $level ??= Level::query()->firstOrCreate(
        ['name' => LevelEnum::NC->value],
        ['description' => 'NC', 'position' => 5, 'show_on_current_application_period' => true],
    );
    $level->update(['show_on_current_application_period' => true]);

    $department = Department::query()->firstOrCreate(
        ['name' => 'Guest Reg Dept '.uniqid()],
        ['description' => 'Test', 'is_academic' => true],
    );
    $department->update(['is_academic' => true]);

    $institutionDepartment = InstitutionDepartment::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'department_id' => $department->id,
        ],
        ['department_code' => 'GRD'],
    );

    $departmentLevel = DepartmentLevel::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
            'level_id' => $level->id,
        ],
    );

    $course = Course::query()->firstOrCreate(
        ['name' => 'Guest Reg Course '.uniqid()],
        ['description' => 'Test'],
    );

    $departmentCourse = DepartmentCourse::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
            'course_id' => $course->id,
        ],
    );

    DepartmentLevelCourse::query()->firstOrCreate([
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
    ]);

    $mode = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::FULL_TIME->value],
        ['description' => 'Full Time'],
    );
    $blockRelease = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::BLOCK_RELEASE->value],
        ['description' => 'Block Release'],
    );

    CourseLevelMode::query()->updateOrCreate(
        [
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $departmentLevel->id,
        ],
        ['modes' => [$mode->id, $blockRelease->id]],
    );

    seedApplicationOffering(
        $institutionDepartment,
        $departmentLevel,
        $departmentCourse,
        [(int) $mode->id, (int) $blockRelease->id],
        hasApprenticeProgrammes: true,
    );

    return [
        'level' => $level,
        'intakeId' => $intake->id,
        'departmentId' => $institutionDepartment->id,
        'departmentLevelId' => $departmentLevel->id,
        'courseId' => $departmentCourse->id,
        'modeId' => $mode->id,
        'blockReleaseModeId' => $blockRelease->id,
    ];
}

/**
 * @param  array{
 *     level: Level,
 *     intakeId: int,
 *     departmentId: int,
 *     departmentLevelId: int,
 *     courseId: int,
 *     modeId: int,
 *     blockReleaseModeId?: int
 * }|null  $seeded
 * @return array<string, mixed>
 */
function guestRegistrationIntentSession(
    ApplicationTrackEnum $track = ApplicationTrackEnum::Regular,
    ?array $seeded = null,
): array {
    $seeded ??= seedGuestRegistrationProgramme();
    $modeId = $track === ApplicationTrackEnum::Apprentice
        ? ($seeded['blockReleaseModeId'] ?? $seeded['modeId'])
        : $seeded['modeId'];

    return [
        RegistrationIntentSession::TRACK_KEY => $track->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
        RegistrationIntentSession::DEPARTMENT_KEY => $seeded['departmentId'],
        RegistrationIntentSession::DEPARTMENT_LEVEL_KEY => $seeded['departmentLevelId'],
        RegistrationIntentSession::COURSE_KEY => $seeded['courseId'],
        RegistrationIntentSession::MODE_KEY => $modeId,
        RegistrationIntentSession::READY_FOR_ACCOUNT_KEY => true,
        RegistrationIntentSession::INSTRUCTIONS_KEY => true,
        RegistrationIntentSession::REQUIRES_FEE_KEY => $track !== ApplicationTrackEnum::Apprentice,
        ...($track === ApplicationTrackEnum::Transfer
            ? [RegistrationIntentSession::TRANSFER_COLLEGE_NAME_KEY => 'Harare College of Technology']
            : []),
    ];
}

/**
 * Seed a continuous-eligible programme with Full Time + OJET modes for SDP/OJET filter tests.
 *
 * @return array{
 *     level: Level,
 *     intakeId: int,
 *     continuousIntakeId: int,
 *     departmentId: int,
 *     departmentLevelId: int,
 *     courseId: int,
 *     fullTimeModeId: int,
 *     ojetModeId: int
 * }
 */
function seedGuestContinuousProgramme(string $focus = 'sdp'): array
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $regular = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $continuous = ensureContinuousIntakeOpen();

    $levelName = $focus === 'sdp' ? LevelEnum::SDP->value : LevelEnum::NC->value;
    $level = Level::query()->firstOrCreate(
        ['name' => $levelName],
        [
            'description' => $levelName,
            'position' => $focus === 'sdp' ? 9 : 5,
            'show_on_current_application_period' => true,
            'has_application_fee_payment' => false,
        ],
    );
    $level->update([
        'show_on_current_application_period' => true,
        'has_application_fee_payment' => false,
    ]);

    $department = Department::query()->firstOrCreate(
        ['name' => 'Guest Continuous Dept '.uniqid()],
        ['description' => 'Test', 'is_academic' => true],
    );
    $department->update(['is_academic' => true]);

    $institutionDepartment = InstitutionDepartment::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'department_id' => $department->id,
        ],
        ['department_code' => 'GCD'],
    );

    $departmentLevel = DepartmentLevel::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
            'level_id' => $level->id,
        ],
    );

    $course = Course::query()->firstOrCreate(
        ['name' => 'Guest Continuous Course '.uniqid()],
        ['description' => 'Test'],
    );

    $departmentCourse = DepartmentCourse::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
            'course_id' => $course->id,
        ],
    );

    DepartmentLevelCourse::query()->firstOrCreate([
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
    ]);

    $fullTime = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::FULL_TIME->value],
        ['description' => 'Full Time'],
    );
    $ojet = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::OJET->value],
        ['description' => 'Ojet'],
    );

    CourseLevelMode::query()->updateOrCreate(
        [
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $departmentLevel->id,
        ],
        ['modes' => [$fullTime->id, $ojet->id]],
    );

    seedApplicationOffering(
        $institutionDepartment,
        $departmentLevel,
        $departmentCourse,
        [(int) $fullTime->id, (int) $ojet->id],
    );

    return [
        'level' => $level,
        'intakeId' => $regular->id,
        'continuousIntakeId' => $continuous->id,
        'departmentId' => $institutionDepartment->id,
        'departmentLevelId' => $departmentLevel->id,
        'courseId' => $departmentCourse->id,
        'fullTimeModeId' => $fullTime->id,
        'ojetModeId' => $ojet->id,
    ];
}
