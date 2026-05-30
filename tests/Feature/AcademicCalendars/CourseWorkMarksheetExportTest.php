<?php

use App\Models\AcademicCalendars\CourseWorkMark;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/../JsonApi/V1/AcademicCalendars/CourseWorkMarkTest.php';

test('course work marksheet page requires view permission', function () {
    $context = createCourseWorkJsonApiContext();

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.course-work-marksheet', [
            'institution_department' => $context['academicCalendarClass']->classConfig->institution_department_id,
            'calendar_year' => $context['academicCalendarClass']->classConfig->calendar_year,
            'class_config_id' => $context['academicCalendarClass']->classConfig->id,
            'department_course_id' => $context['academicCalendarClass']->classConfig->department_course_id,
            'department_level_id' => $context['academicCalendarClass']->classConfig->department_level_id,
            'mode_of_study_id' => $context['academicCalendarClass']->classConfig->mode_of_study_id,
        ]))
        ->assertForbidden();
});

test('authorized user can view class config course work marksheet page', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.course-work-marksheet', [
            'institution_department' => $context['academicCalendarClass']->classConfig->institution_department_id,
            'calendar_year' => $context['academicCalendarClass']->classConfig->calendar_year,
            'class_config_id' => $context['academicCalendarClass']->classConfig->id,
            'department_course_id' => $context['academicCalendarClass']->classConfig->department_course_id,
            'department_level_id' => $context['academicCalendarClass']->classConfig->department_level_id,
            'mode_of_study_id' => $context['academicCalendarClass']->classConfig->mode_of_study_id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/academicCalendars/DepartmentAcademicCalendarClassConfigCourseWorkMarksheet')
            ->has('classConfig'));
});

test('course work marksheet export requires export permission', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.course-work-marksheet.export', [
            'institution_department' => $context['academicCalendarClass']->classConfig->institution_department_id,
            'calendar_year' => $context['academicCalendarClass']->classConfig->calendar_year,
            'class_config_id' => $context['academicCalendarClass']->classConfig->id,
            'module' => $context['module']->id,
            'format' => 'xlsx',
        ]))
        ->assertForbidden();
});

test('authorized user can export class config course work marksheet excel', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('export:course-work', 'web');
    $context['user']->givePermissionTo('export:course-work');

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 75,
        'created_by' => $context['user']->id,
        'updated_by' => $context['user']->id,
    ]);

    $response = $this->actingAs($context['user'])->get(route('academic-calendars.department-classes.course-work-marksheet.export', [
        'institution_department' => $context['academicCalendarClass']->classConfig->institution_department_id,
        'calendar_year' => $context['academicCalendarClass']->classConfig->calendar_year,
        'class_config_id' => $context['academicCalendarClass']->classConfig->id,
        'module' => $context['module']->id,
        'format' => 'xlsx',
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

test('json api course work class config tree returns students under config', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => ['classConfig' => $context['academicCalendarClass']->class_config_id],
        ]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.classConfigId', $context['academicCalendarClass']->class_config_id)
        ->assertJsonPath('meta.syllabi.0.modules.0.students.0.studentEnrolmentId', $context['studentEnrolment']->id)
        ->assertJsonPath('meta.marksheetSummary.0.moduleId', $context['module']->id);
});

test('json api course work store with class config filter validates enrolment', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');

    Sanctum::actingAs($context['user']);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 65,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', [
            'filter' => ['classConfig' => $context['academicCalendarClass']->class_config_id],
        ]))
        ->assertCreated();
});

test('json api course work store rejects enrolment outside class config', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');
    Sanctum::actingAs($context['user']);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 65,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', [
            'filter' => ['classConfig' => 999_999],
        ]))
        ->assertStatus(422);
});
