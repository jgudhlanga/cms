<?php

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Services\AcademicCalendars\ClassListDataService;
use App\Services\AcademicCalendars\ClassListPdfService;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/../../Support/AcademicCalendarClassTestHelpers.php';

function createPopulatedClassContext(): array
{
    $context = buildDepartmentClassContext();
    createFinalStudentApplication($context, 'class-export-one@example.com');
    createFinalStudentApplication($context, 'class-export-two@example.com');

    $context['user']->givePermissionTo([
        'export:academic-calendars',
        'update:academic-calendars',
    ]);

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

    $context['academicCalendarClass'] = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->orderBy('id')
        ->firstOrFail();

    return $context;
}

test('class list export requires export permission', function () {
    $context = createPopulatedClassContext();
    $context['user']->revokePermissionTo('export:academic-calendars');

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'class_config_id' => $context['classConfig']->id,
            'class_ids' => [$context['academicCalendarClass']->id],
        ]))
        ->assertForbidden();
});

test('class list export rejects empty class selection', function () {
    $context = createPopulatedClassContext();

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'class_config_id' => $context['classConfig']->id,
            'class_ids' => [],
        ]))
        ->assertSessionHasErrors('class_ids');
});

test('authorized user can export class list pdf for selected classes', function () {
    Permission::findOrCreate('export:academic-calendars', 'web');
    $context = createPopulatedClassContext();

    $response = $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'class_config_id' => $context['classConfig']->id,
            'class_ids' => [$context['academicCalendarClass']->id],
        ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('authorized user can export single class list pdf from show route', function () {
    Permission::findOrCreate('export:academic-calendars', 'web');
    $context = createPopulatedClassContext();

    $response = $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export-class', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('class list export pdf view data matches enrolled student count', function () {
    Permission::findOrCreate('export:academic-calendars', 'web');
    $context = createPopulatedClassContext();

    $data = app(ClassListDataService::class)->assembleForClassConfig(
        $context['classConfig'],
        [$context['academicCalendarClass']->id],
    );

    $viewData = app(ClassListPdfService::class)->assembleViewData(
        $data,
        $context['institutionDepartment']->tenant_id,
    );

    $rows = collect($viewData['sections'][0]['pages'])->flatMap(fn (array $page) => $page['rows'])->values();

    expect($viewData['documentTemplate'])->not->toBeNull()
        ->and($rows)->toHaveCount(2)
        ->and($rows->pluck('number')->all())->toBe([1, 2]);

    $html = view('academic-calendars.class-list', $viewData)->render();

    expect($html)
        ->toContain('Enrolment: Class list')
        ->toContain('Compiled by:')
        ->toContain('-- 1 of 1 --')
        ->toContain('class="content page-main"')
        ->not->toContain('Class:');
});

test('class list export renders header once and signatures on final page for multi-page classes', function () {
    Permission::findOrCreate('export:academic-calendars', 'web');

    $context = buildDepartmentClassContext();
    $context['user']->givePermissionTo([
        'export:academic-calendars',
        'update:academic-calendars',
    ]);

    for ($index = 1; $index <= 23; $index++) {
        createFinalStudentApplication($context, "class-export-multi-{$index}@example.com");
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

    $academicCalendarClass = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->orderBy('id')
        ->firstOrFail();

    $data = app(ClassListDataService::class)->assembleForClassConfig(
        $context['classConfig'],
        [$academicCalendarClass->id],
    );

    $viewData = app(ClassListPdfService::class)->assembleViewData(
        $data,
        $context['institutionDepartment']->tenant_id,
    );

    expect($viewData['sections'][0]['pages'])->toHaveCount(2)
        ->and($viewData['sections'][0]['pages'][0]['isFirstPage'])->toBeTrue()
        ->and($viewData['sections'][0]['pages'][1]['isFirstPage'])->toBeFalse()
        ->and($viewData['sections'][0]['pages'][1]['isLastPage'])->toBeTrue();

    $html = view('academic-calendars.class-list', $viewData)->render();

    expect(substr_count($html, 'Enrolment: Class list'))->toBe(1)
        ->and(substr_count($html, 'Compiled by:'))->toBe(1)
        ->and(substr_count($html, 'class="content page-main"'))->toBe(2)
        ->and(substr_count($html, 'class="content page-footer-section"'))->toBe(1)
        ->and(substr_count($html, '-- 1 of 2 --'))->toBe(1)
        ->and(substr_count($html, '-- 2 of 2 --'))->toBe(1);
});
