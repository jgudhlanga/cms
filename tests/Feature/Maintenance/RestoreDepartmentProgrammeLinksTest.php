<?php

declare(strict_types=1);

use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Students\StudentApplication;
use App\Models\Tenants\Tenant;
use App\Queries\Maintenance\FaultyApplicationsQuery;

/**
 * Reproduces the leak: the original link is soft-deleted, a replacement id is
 * created for the same catalog level, and the application still points at the
 * original.
 */
function orphanedLevelFixture(string $studentNumber): array
{
    $application = createVerifiedStudentApplication($studentNumber);
    $original = $application->departmentLevel;
    $original->delete();

    $replacement = DepartmentLevel::query()->create([
        'tenant_id' => Tenant::query()->firstOrFail()->id,
        'institution_department_id' => $original->institution_department_id,
        'level_id' => $original->level_id,
    ]);

    return [$application, $original, $replacement];
}

it('reports the repair without changing data by default', function (): void {
    [, $original, $replacement] = orphanedLevelFixture('STU-REPAIR-DRY');

    $this->artisan('maintenance:restore-department-programme-links')
        ->assertSuccessful();

    expect($original->fresh()->deleted_at)->not->toBeNull()
        ->and($replacement->fresh()->deleted_at)->toBeNull();
});

it('restores the original link and retires the replacement', function (): void {
    [$application, $original, $replacement] = orphanedLevelFixture('STU-REPAIR-RUN');

    $this->artisan('maintenance:restore-department-programme-links', ['--execute' => true])
        ->assertSuccessful();

    expect($original->fresh()->deleted_at)->toBeNull()
        ->and($replacement->fresh()->deleted_at)->not->toBeNull()
        ->and((int) $application->fresh()->department_level_id)->toBe((int) $original->id);
});

it('clears the faulty applications list', function (): void {
    orphanedLevelFixture('STU-REPAIR-FAULTY');

    expect(app(FaultyApplicationsQuery::class)->count())->toBeGreaterThan(0);

    $this->artisan('maintenance:restore-department-programme-links', ['--execute' => true])
        ->assertSuccessful();

    expect(app(FaultyApplicationsQuery::class)->count())->toBe(0);
});

it('keeps the id most applications already use', function (): void {
    [$application, $original, $replacement] = orphanedLevelFixture('STU-REPAIR-MAJORITY');

    // Two applications now sit on the replacement, one on the original.
    $second = createVerifiedStudentApplication('STU-REPAIR-MAJORITY-2');
    $third = createVerifiedStudentApplication('STU-REPAIR-MAJORITY-3');
    StudentApplication::query()->whereIn('id', [$second->id, $third->id])->update([
        'institution_department_id' => $original->institution_department_id,
        'department_level_id' => $replacement->id,
    ]);

    $this->artisan('maintenance:restore-department-programme-links', ['--execute' => true])
        ->assertSuccessful();

    expect($replacement->fresh()->deleted_at)->toBeNull()
        ->and($original->fresh()->deleted_at)->not->toBeNull()
        ->and((int) $application->fresh()->department_level_id)->toBe((int) $replacement->id);
});

it('merges pivot rows that would collide on remap', function (): void {
    [, $original, $replacement] = orphanedLevelFixture('STU-REPAIR-PIVOT');

    $departmentCourseId = DepartmentLevelCourse::query()
        ->where('department_level_id', $original->id)
        ->value('department_course_id');

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourseId,
        'department_level_id' => $replacement->id,
    ]);

    $this->artisan('maintenance:restore-department-programme-links', ['--execute' => true])
        ->assertSuccessful();

    $pivots = DepartmentLevelCourse::query()
        ->where('department_course_id', $departmentCourseId)
        ->whereIn('department_level_id', [$original->id, $replacement->id])
        ->get();

    expect($pivots)->toHaveCount(1)
        ->and((int) $pivots->first()->department_level_id)->toBe((int) $original->id);
});

it('leaves healthy departments untouched', function (): void {
    $application = createVerifiedStudentApplication('STU-REPAIR-HEALTHY');

    $this->artisan('maintenance:restore-department-programme-links')
        ->expectsOutput('No orphaned department level or course links found.')
        ->assertSuccessful();

    expect($application->departmentLevel->fresh()->deleted_at)->toBeNull();
});
