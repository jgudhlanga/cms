<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Support\Institution\CourseSyllabusModulePeriod;

it('does not match a later-stage module against an earlier programme semester', function (): void {
    $ctx = makeSyllabusModuleContext();

    $dlc = $ctx['courseSyllabus']->departmentLevelCourse;
    $dlc->update([
        'duration_years' => 2,
        'taught_semester_count' => 4,
        'includes_industrial_attachment' => false,
        'attachment_semester_count' => 0,
    ]);
    $dlc->departmentLevel->level->update(['name' => 'NC', 'calendar_type' => 'semester']);

    $semesters = app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc->fresh(['departmentLevel.level']));
    $nc1Sem1 = programmeSemesterAt($semesters, 1, 1);
    $nc2Sem1 = programmeSemesterAt($semesters, 2, 1);

    expect($nc1Sem1)->not->toBeNull()->and($nc2Sem1)->not->toBeNull();

    $moduleNc1 = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'semester_id' => $ctx['semesterOne']->id,
        'programme_semester_id' => $nc1Sem1?->id,
        'title' => 'NC1 Module',
        'code' => 'NC1-'.uniqid(),
        'shared' => false,
        'all_semesters' => false,
    ]);

    $moduleNc2 = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'semester_id' => $ctx['semesterOne']->id,
        'programme_semester_id' => $nc2Sem1?->id,
        'title' => 'NC2 Module',
        'code' => 'NC2-'.uniqid(),
        'shared' => false,
        'all_semesters' => false,
    ]);

    expect(CourseSyllabusModulePeriod::matchesPeriod($moduleNc1, (int) $ctx['semesterOne']->id, (int) $nc1Sem1?->id))->toBeTrue()
        ->and(CourseSyllabusModulePeriod::matchesPeriod($moduleNc2, (int) $ctx['semesterOne']->id, (int) $nc1Sem1?->id))->toBeFalse()
        ->and(CourseSyllabusModulePeriod::matchesPeriod($moduleNc2, (int) $ctx['semesterOne']->id, (int) $nc2Sem1?->id))->toBeTrue();

    $matched = CourseSyllabusModule::query()
        ->where('course_syllabus_id', $ctx['courseSyllabus']->id)
        ->where(function ($query) use ($ctx, $nc1Sem1): void {
            CourseSyllabusModulePeriod::scopeForPeriod(
                $query,
                (int) $ctx['semesterOne']->id,
                'semester',
                (int) $nc1Sem1?->id,
            );
        })
        ->pluck('id')
        ->all();

    expect($matched)->toContain($moduleNc1->id)
        ->and($matched)->not->toContain($moduleNc2->id);
});
