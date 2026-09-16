<?php

use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Setup\SetupGap;
use Illuminate\Support\Facades\DB;

function lecturerInChargeGapsFor(int $institutionDepartmentId)
{
    return SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value)
        ->where('institution_department_id', $institutionDepartmentId);
}

it('raises a gap for an open class with no lecturer in charge', function () {
    $context = makeSetupGapClassContext();

    scanSetupGaps();

    $gap = lecturerInChargeGapsFor($context['institutionDepartment']->id)->first();

    expect($gap)->not->toBeNull()
        ->and($gap->tenant_id)->toBe($context['tenantId'])
        ->and($gap->resolved_at)->toBeNull()
        ->and($gap->notified_at)->toBeNull()
        ->and($gap->meta['classConfigId'] ?? null)->toBe($context['classConfigId']);
});

it('does not raise the same gap twice when it is still present', function () {
    $context = makeSetupGapClassContext();

    scanSetupGaps();
    $firstDetectedAt = lecturerInChargeGapsFor($context['institutionDepartment']->id)->first()->detected_at;

    $summary = scanSetupGaps();

    expect(lecturerInChargeGapsFor($context['institutionDepartment']->id)->count())->toBe(1)
        ->and($summary['raised'])->toBe(0);

    // The original detection date survives, so "found on" keeps telling the truth.
    expect(lecturerInChargeGapsFor($context['institutionDepartment']->id)->first()->detected_at->toDateTimeString())
        ->toBe($firstDetectedAt->toDateTimeString());
});

it('closes the gap once the class has a lecturer in charge', function () {
    seedDashboardTestRoles();
    $context = makeSetupGapClassContext();
    scanSetupGaps();

    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    DB::table('class_config_lecturers_in_charge')->insert([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $context['classConfigId'],
        'staff_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    scanSetupGaps();

    expect(lecturerInChargeGapsFor($context['institutionDepartment']->id)->first()->resolved_at)->not->toBeNull();
});

it('reopens a gap that comes back after being fixed', function () {
    $context = makeSetupGapClassContext();
    scanSetupGaps();

    // Pretend it was fixed and announced, then broke again.
    lecturerInChargeGapsFor($context['institutionDepartment']->id)
        ->update(['resolved_at' => now(), 'notified_at' => now()]);

    scanSetupGaps();

    $gap = lecturerInChargeGapsFor($context['institutionDepartment']->id)->first();

    expect($gap->resolved_at)->toBeNull()
        ->and($gap->notified_at)->toBeNull();
});

it('raises a gap for an open class with no modules attached', function () {
    $context = makeSetupGapClassContext();

    scanSetupGaps();

    $gap = SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS->value)
        ->where('institution_department_id', $context['institutionDepartment']->id)
        ->first();

    expect($gap)->not->toBeNull()
        ->and($gap->severity->value)->toBe('warning');
});

it('leaves a class with modules alone', function () {
    $context = makeSetupGapClassContext(['course_syllabus_ids' => [1, 2]]);

    scanSetupGaps();

    expect(
        SetupGap::query()
            ->where('check_key', SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS->value)
            ->where('institution_department_id', $context['institutionDepartment']->id)
            ->exists(),
    )->toBeFalse();
});
