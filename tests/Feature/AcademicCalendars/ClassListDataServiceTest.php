<?php

use App\Services\AcademicCalendars\ClassListDataService;

require_once __DIR__.'/../../Support/AcademicCalendarClassTestHelpers.php';

test('assembleForClass returns one row per enrolled student without padding', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentApplication($context, 'class-list-row-one@example.com');
    createFinalStudentApplication($context, 'class-list-row-two@example.com');

    $context['user']->givePermissionTo(['update:academic-calendars']);

    test()->actingAs($context['user'])->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $class = \App\Models\AcademicCalendars\AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->firstOrFail();

    $section = app(ClassListDataService::class)->assembleForClass($class, $context['classConfig']);

    expect($section['studentCount'])->toBe(2)
        ->and($section['totalPages'])->toBe(1)
        ->and($section['pages'])->toHaveCount(1)
        ->and($section['pages'][0]['isFirstPage'])->toBeTrue()
        ->and($section['pages'][0]['rows'])->toHaveCount(2)
        ->and($section['pages'][0]['rows'][0]['number'])->toBe(1)
        ->and($section['pages'][0]['rows'][1]['number'])->toBe(2)
        ->and($section['pages'][0]['isLastPage'])->toBeTrue();
});

test('assembleForClass paginates without padding intermediate pages', function () {
    $context = buildDepartmentClassContext();
    $context['user']->givePermissionTo(['update:academic-calendars']);

    for ($index = 1; $index <= 23; $index++) {
        createFinalStudentApplication($context, "class-list-page-{$index}@example.com");
    }

    test()->actingAs($context['user'])->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 23,
    ])->assertSessionHas('success');

    $class = \App\Models\AcademicCalendars\AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->firstOrFail();

    $section = app(ClassListDataService::class)->assembleForClass($class, $context['classConfig']);

    expect($section['studentCount'])->toBe(23)
        ->and($section['totalPages'])->toBe(2)
        ->and($section['pages'][0]['isFirstPage'])->toBeTrue()
        ->and($section['pages'][0]['rows'])->toHaveCount(ClassListDataService::ROWS_PER_FIRST_PAGE)
        ->and($section['pages'][1]['isFirstPage'])->toBeFalse()
        ->and($section['pages'][1]['rows'])->toHaveCount(5)
        ->and($section['pages'][0]['isLastPage'])->toBeFalse()
        ->and($section['pages'][1]['isLastPage'])->toBeTrue()
        ->and($section['pages'][1]['rows'][0]['number'])->toBe(19)
        ->and($section['pages'][1]['rows'][4]['number'])->toBe(23);
});

test('assembleForClass sorts students by first name then last name', function () {
    $context = buildDepartmentClassContext();
    $context['user']->givePermissionTo(['update:academic-calendars']);

    createFinalStudentApplication($context, 'zebra@example.com');
    createFinalStudentApplication($context, 'alpha@example.com');

    test()->actingAs($context['user'])->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $class = \App\Models\AcademicCalendars\AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->firstOrFail();

    $section = app(ClassListDataService::class)->assembleForClass($class, $context['classConfig']);
    $surnames = collect($section['pages'][0]['rows'])->pluck('surname')->all();

    expect($surnames)->toBe(['alpha', 'zebra']);
});
