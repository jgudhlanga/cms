<?php

use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Students\StudentEnrolment;
use App\Support\Rbac\PermissionRegistry;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('json api course work tree requires permission', function () {
    $context = createCourseWorkJsonApiContext();
    Sanctum::actingAs($context['user']);

    $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertForbidden();
});

test('json api course work tree returns syllabi modules and students', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.academicCalendarClassId', $context['academicCalendarClass']->id)
        ->assertJsonPath('meta.syllabi.0.modules.0.id', $context['module']->id)
        ->assertJsonPath('meta.syllabi.0.modules.0.students.0.studentEnrolmentId', $context['studentEnrolment']->id)
        ->assertJsonPath('meta.assessmentTypes.0.id', $context['assessmentType']->id);
});

test('json api course work store creates mark and audit log', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 72,
                'remark' => 'Good work',
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]));

    $response->assertCreated()
        ->assertJsonPath('data.attributes.mark', 72)
        ->assertJsonPath('data.attributes.remark', 'Good work');

    $mark = CourseWorkMark::query()->first();
    expect($mark)->not->toBeNull()
        ->and($mark->mark)->toBe(72);

    expect(CourseWorkAuditLog::query()->where('course_work_mark_id', $mark->id)->count())->toBe(1);
});

test('json api course work store rejects marks above 100', function () {
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
                'mark' => 101,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertStatus(422);
});

test('json api course work store rejects decimal marks', function () {
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
                'mark' => 72.5,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store'))
        ->assertStatus(422);
});

test('json api course work update writes audit log', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    Permission::findOrCreate('update:course-work', 'web');
    $context['user']->givePermissionTo(['create:course-work', 'update:course-work']);
    Sanctum::actingAs($context['user']);

    $mark = CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 50,
        'created_by' => $context['user']->id,
        'updated_by' => $context['user']->id,
    ]);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'id' => (string) $mark->id,
            'attributes' => [
                'mark' => 88,
                'remark' => 'Improved',
            ],
        ])
        ->patch(route('v1.json.course-work-marks.update', $mark->id, ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertSuccessful();

    expect($mark->refresh()->mark)->toBe(88);
    expect(CourseWorkAuditLog::query()->where('course_work_mark_id', $mark->id)->where('event', 'updated')->count())->toBe(1);
});

test('json api course work student tree returns modules with assessments only', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => [
                'academicCalendarClass' => $context['academicCalendarClass']->id,
                'studentEnrolment' => $context['studentEnrolment']->id,
            ],
        ]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.studentEnrolmentId', $context['studentEnrolment']->id)
        ->assertJsonPath('meta.syllabi.0.modules.0.assessments.0.assessmentTypeId', $context['assessmentType']->id)
        ->assertJsonMissingPath('meta.syllabi.0.modules.0.students');
});

test('json api course work student tree rejects enrolment not in class', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $otherEnrolment = StudentEnrolment::query()->create([
        'student_id' => $context['studentEnrolment']->student_id,
        'student_application_id' => $context['studentEnrolment']->student_application_id,
        'institution_department_id' => $context['studentEnrolment']->institution_department_id,
        'department_level_id' => $context['studentEnrolment']->department_level_id,
        'department_course_id' => $context['studentEnrolment']->department_course_id,
        'academic_year_option_id' => $context['studentEnrolment']->academic_year_option_id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'mode_of_study_id' => $context['studentEnrolment']->mode_of_study_id,
        'student_enrolment_status_id' => $context['studentEnrolment']->student_enrolment_status_id,
    ]);

    $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => [
                'academicCalendarClass' => $context['academicCalendarClass']->id,
                'studentEnrolment' => $otherEnrolment->id,
            ],
        ]))
        ->assertStatus(422);
});

test('permission registry resolves course work module title', function () {
    expect(PermissionRegistry::moduleTitleForGroupKey('course-work'))->toBe('Course Work');
});

test('json api course work store creates mark-only row without assessment type', function () {
    $context = createCourseWorkJsonApiContext();
    $context['module']->update(['capture_mark_only' => true]);

    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');
    Sanctum::actingAs($context['user']);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'mark' => 81,
                'remark' => 'Direct mark',
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertCreated()
        ->assertJsonPath('data.attributes.mark', 81);

    $mark = CourseWorkMark::query()->whereNull('assessment_type_id')->first();
    expect($mark)->not->toBeNull()
        ->and($mark->mark)->toBe(81);
});

test('json api course work store rejects assessment type on mark-only module', function () {
    $context = createCourseWorkJsonApiContext();
    $context['module']->update(['capture_mark_only' => true]);

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
                'mark' => 70,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertStatus(422);
});

test('json api course work tree exposes mark-only module payload', function () {
    $context = createCourseWorkJsonApiContext();
    $context['module']->update(['capture_mark_only' => true]);

    Permission::findOrCreate('viewAny:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['viewAny:course-work', 'create:course-work']);
    Sanctum::actingAs($context['user']);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => null,
        'mark' => 65,
        'created_by' => $context['user']->id,
        'updated_by' => $context['user']->id,
    ]);

    $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertSuccessful()
        ->assertJsonPath('meta.syllabi.0.modules.0.captureMarkOnly', true)
        ->assertJsonPath('meta.syllabi.0.modules.0.students.0.moduleMark.mark', 65)
        ->assertJsonMissingPath('meta.syllabi.0.modules.0.students.0.aggregation');
});
