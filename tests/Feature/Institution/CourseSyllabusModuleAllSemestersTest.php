<?php

use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Support\Institution\CourseSyllabusModulePeriod;

require_once __DIR__.'/../../Support/SyllabusModuleTestHelpers.php';

it('matches module period exactly or when all_semesters is enabled', function () {
    $ctx = makeSyllabusModuleContext();

    $module = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Shared Module',
        'code' => 'MOD-ALL-'.uniqid(),
        'all_semesters' => true,
    ]);

    expect(CourseSyllabusModulePeriod::matchesPeriod($module, (int) $ctx['semesterOne']->id))->toBeTrue()
        ->and(CourseSyllabusModulePeriod::matchesPeriod($module, (int) $ctx['semesterTwo']->id))->toBeTrue();
});

it('does not match all_semesters module for a different calendar type', function () {
    $ctx = makeSyllabusModuleContext();

    $module = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Semester Module',
        'code' => 'MOD-SEM-'.uniqid(),
        'all_semesters' => true,
    ]);

    expect(CourseSyllabusModulePeriod::matchesPeriod($module, (int) $ctx['termOne']->id))->toBeFalse();
});
