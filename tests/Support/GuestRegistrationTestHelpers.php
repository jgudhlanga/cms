<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationTrackEnum;
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
        ['description' => 'Test'],
    );

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
        ['show_on_current_application_period' => true],
    );
    $departmentLevel->update(['show_on_current_application_period' => true]);

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
        ['show_on_current_application_period' => true],
    );
    $departmentCourse->update(['show_on_current_application_period' => true]);

    DepartmentLevelCourse::query()->firstOrCreate([
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
    ]);

    $mode = ModeOfStudy::query()->firstOrCreate(
        ['name' => 'Full Time'],
        ['description' => 'Full Time'],
    );

    CourseLevelMode::query()->updateOrCreate(
        [
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $departmentLevel->id,
        ],
        ['modes' => [$mode->id]],
    );

    return [
        'level' => $level,
        'intakeId' => $intake->id,
        'departmentId' => $institutionDepartment->id,
        'departmentLevelId' => $departmentLevel->id,
        'courseId' => $departmentCourse->id,
        'modeId' => $mode->id,
    ];
}

/**
 * @param  array{level: Level, intakeId: int, departmentId: int, departmentLevelId: int, courseId: int, modeId: int}|null  $seeded
 * @return array<string, mixed>
 */
function guestRegistrationIntentSession(
    ApplicationTrackEnum $track = ApplicationTrackEnum::Regular,
    ?array $seeded = null,
): array {
    $seeded ??= seedGuestRegistrationProgramme();

    return [
        RegistrationIntentSession::TRACK_KEY => $track->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
        RegistrationIntentSession::DEPARTMENT_KEY => $seeded['departmentId'],
        RegistrationIntentSession::DEPARTMENT_LEVEL_KEY => $seeded['departmentLevelId'],
        RegistrationIntentSession::COURSE_KEY => $seeded['courseId'],
        RegistrationIntentSession::MODE_KEY => $seeded['modeId'],
        RegistrationIntentSession::READY_FOR_ACCOUNT_KEY => true,
        RegistrationIntentSession::INSTRUCTIONS_KEY => true,
        RegistrationIntentSession::REQUIRES_FEE_KEY => $track !== ApplicationTrackEnum::Apprentice,
    ];
}
