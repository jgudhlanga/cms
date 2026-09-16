<?php

use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Setup\SetupGap;
use Illuminate\Support\Str;

/**
 * Builds an application in the department's own mode, and makes its intake the one the scan looks at.
 *
 * @return array<string, mixed>
 */
function makeSetupGapApplicationContext(): array
{
    $context = makeDepartmentReconciliationContext();
    $student = createDepartmentReconciliationStudent($context, 'SG-'.strtoupper(Str::random(6)));

    // The scan reads the current intake (latest end date), so this one has to win outright.
    IntakePeriod::query()
        ->whereKey($student['application']->intake_period_id)
        ->update(['end_date' => now()->addYear()->toDateString()]);

    return [...$context, 'application' => $student['application']];
}

function programmeGap(SetupGapCheckEnum $check, int $institutionDepartmentId): ?SetupGap
{
    return SetupGap::query()
        ->where('check_key', $check->value)
        ->where('institution_department_id', $institutionDepartmentId)
        ->first();
}

it('raises a gap when applications sit in a mode the course level is not set up for', function () {
    $context = makeSetupGapApplicationContext();

    // The course level is set up for a different mode entirely — the Applied Arts case.
    $otherMode = ModeOfStudy::query()->create(['name' => 'Ojet '.Str::random(5)]);
    CourseLevelMode::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'modes' => [$otherMode->id],
    ]);

    scanSetupGaps();

    $gap = programmeGap(SetupGapCheckEnum::APPLICATIONS_IN_UNCONFIGURED_MODE, $context['institutionDepartment']->id);

    expect($gap)->not->toBeNull()
        ->and($gap->severity->value)->toBe('critical')
        ->and($gap->meta['modeOfStudyId'] ?? null)->toBe((int) $context['modeOfStudy']->id)
        ->and($gap->meta['applicationCount'] ?? null)->toBe(1);
});

it('stays quiet when the applications are in a mode that is set up', function () {
    $context = makeSetupGapApplicationContext();

    CourseLevelMode::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'modes' => [$context['modeOfStudy']->id],
    ]);

    scanSetupGaps();

    expect(programmeGap(SetupGapCheckEnum::APPLICATIONS_IN_UNCONFIGURED_MODE, $context['institutionDepartment']->id))
        ->toBeNull();
});

it('raises a gap when the course level has no modes set up at all', function () {
    $context = makeSetupGapApplicationContext();

    scanSetupGaps();

    $gap = programmeGap(SetupGapCheckEnum::COURSE_LEVEL_WITHOUT_MODES, $context['institutionDepartment']->id);

    expect($gap)->not->toBeNull()
        ->and($gap->meta['departmentCourseId'] ?? null)->toBe((int) $context['departmentCourse']->id);

    // The narrower "wrong mode" gap does not also fire: there is no configuration to contradict.
    expect(programmeGap(SetupGapCheckEnum::APPLICATIONS_IN_UNCONFIGURED_MODE, $context['institutionDepartment']->id))
        ->toBeNull();
});

it('closes the gap once the missing mode is added', function () {
    $context = makeSetupGapApplicationContext();
    scanSetupGaps();

    CourseLevelMode::query()->create([
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'modes' => [$context['modeOfStudy']->id],
    ]);

    scanSetupGaps();

    $gap = SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::COURSE_LEVEL_WITHOUT_MODES->value)
        ->where('institution_department_id', $context['institutionDepartment']->id)
        ->first();

    expect($gap->resolved_at)->not->toBeNull();
});
