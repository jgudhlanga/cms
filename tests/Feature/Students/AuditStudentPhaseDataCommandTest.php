<?php

declare(strict_types=1);

use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentSemester;

it('reports phase drift and history gaps without writing anything', function (): void {
    $context = makeDepartmentReconciliationContext();
    $offering = makeMultiYearOffering($context, years: 2);
    $phases = $offering['phases'];

    // Drift: Year 2 Sem 1 (position 3) sits on the "semester-1" slot, so the old slug ordering
    // called the semester-2 row current while the new ordering correctly picks this one.
    $drifting = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $phases[2]],
        ['slug' => 'semester-2', 'phase' => $phases[1]],
    ]);

    // Gap: pinned at position 3 with nothing at 1 or 2.
    $gapped = makeEnrolmentWithPhases($context, $offering, [
        ['slug' => 'semester-1', 'phase' => $phases[2]],
    ]);

    $before = StudentSemester::query()->get()->map->only(['id', 'programme_semester_id', 'semester_id'])->toArray();

    $this->artisan('students:audit-phase-data')
        ->expectsOutputToContain('Auditing student phase data')
        ->assertSuccessful();

    // The audit must be read-only.
    $after = StudentSemester::query()->get()->map->only(['id', 'programme_semester_id', 'semester_id'])->toArray();

    expect($after)->toBe($before)
        ->and($drifting->fresh()->studentSemesters)->toHaveCount(2)
        ->and($gapped->fresh()->studentSemesters)->toHaveCount(1);
});
