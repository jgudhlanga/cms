<?php

use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Setup\SetupGap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function structureGap(SetupGapCheckEnum $check): ?SetupGap
{
    return SetupGap::query()->where('check_key', $check->value)->first();
}

function makeDivision(?int $headStaffId = null): int
{
    return DB::table('divisions')->insertGetId([
        'name' => 'Division '.Str::random(6),
        'head_of_division_id' => $headStaffId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('raises a gap for a division with no head', function () {
    $divisionId = makeDivision();

    scanSetupGaps();

    $gap = structureGap(SetupGapCheckEnum::DIVISION_WITHOUT_HEAD);

    expect($gap)->not->toBeNull()
        ->and($gap->meta['divisionId'] ?? null)->toBe($divisionId)
        ->and($gap->severity->value)->toBe('warning')
        ->and($gap->institution_department_id)->toBeNull();
});

it('closes the division gap once a head is assigned', function () {
    seedDashboardTestRoles();
    $context = makeSetupGapClassContext();
    $divisionId = makeDivision();

    scanSetupGaps();
    expect(structureGap(SetupGapCheckEnum::DIVISION_WITHOUT_HEAD)->resolved_at)->toBeNull();

    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-division');
    DB::table('divisions')->where('id', $divisionId)->update([
        'head_of_division_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
    ]);

    scanSetupGaps();

    expect(structureGap(SetupGapCheckEnum::DIVISION_WITHOUT_HEAD)->resolved_at)->not->toBeNull();
});

it('stays quiet about unlinked departments when no division exists', function () {
    makeSetupGapClassContext();

    scanSetupGaps();

    expect(structureGap(SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION))->toBeNull();
});

it('raises one gap per tenant for departments not linked to a division', function () {
    $first = makeSetupGapClassContext();
    makeSetupGapClassContext();
    makeDivision();

    scanSetupGaps();

    $gaps = SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION->value)
        ->get();

    // Two unlinked departments, but one gap — not one alert each.
    expect($gaps)->toHaveCount(1)
        ->and($gaps->first()->tenant_id)->toBe($first['tenantId'])
        ->and($gaps->first()->meta['departmentCount'] ?? 0)->toBeGreaterThanOrEqual(2);
});

it('closes the department gap once every department has a division', function () {
    $context = makeSetupGapClassContext();
    $divisionId = makeDivision();

    scanSetupGaps();
    expect(structureGap(SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION))->not->toBeNull();

    DB::table('institution_departments')->whereNull('division_id')->update(['division_id' => $divisionId]);

    scanSetupGaps();

    expect(structureGap(SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION)->resolved_at)->not->toBeNull()
        ->and($context['institutionDepartment']->fresh()->division_id)->toBe($divisionId);
});
