<?php

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Rbac\Permission;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    enableDashboardModule();
    seedDashboardAcademicCalendar();
});

test('assigned lecturer can open teaching class show', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.show', $context['academicCalendarClass']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Show')
            ->where('classDetail.id', $context['academicCalendarClass']->id)
            ->has('classDetail.students', 1)
            ->where('classDetail.students.0.studentEnrolmentId', $context['studentEnrolment']->id)
            ->has('classDetail.modules', 1)
        );
});

test('unassigned lecturer cannot open teaching class show', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser] = createLecturerUserWithStaff($context);
    prepareLecturerCalendar($context);

    $otherClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $context['tenant']->id,
        'class_config_id' => $context['classConfig']->id,
        'name' => 'UNASSIGNED-CLASS',
        'description' => null,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.show', $otherClass))
        ->assertForbidden();
});

test('assigned lecturer can open marksheet and unassigned module is forbidden', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.marksheet', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'course_syllabus_module' => $context['module']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Marksheet')
            ->where('module.id', $context['module']->id)
        );

    $otherModule = CourseSyllabusModule::query()->create([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_id' => $context['module']->course_syllabus_id,
        'academic_year_option_id' => $context['module']->academic_year_option_id,
        'title' => 'Other Module',
        'code' => 'OTH999',
        'duration_in_hours' => 20,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.marksheet', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'course_syllabus_module' => $otherModule->id,
        ]))
        ->assertForbidden();
});

test('assigned lecturer can store course work marks for assigned class module', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    prepareLecturerCalendar($context);

    // Lecturers use view:academic-calendars, not viewAny — assignment scoping applies.
    Sanctum::actingAs($lecturerUser);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 68,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
        ]))
        ->assertCreated();

    expect(CourseWorkMark::query()->where('mark', 68)->exists())->toBeTrue();
});

test('lecturer cannot store course work marks for unassigned module', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    prepareLecturerCalendar($context);

    $otherModule = CourseSyllabusModule::query()->create([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_id' => $context['module']->course_syllabus_id,
        'academic_year_option_id' => $context['module']->academic_year_option_id,
        'title' => 'Forbidden Module',
        'code' => 'FORB1',
        'duration_in_hours' => 20,
    ]);

    Sanctum::actingAs($lecturerUser);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $otherModule->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 50,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
        ]))
        ->assertForbidden();
});

test('academic admin course work access is unchanged without lecturer assignment', function () {
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
                'mark' => 77,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
        ]))
        ->assertCreated();
});

test('assigned lecturer can export class list and marksheet', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.class-list.export', $context['academicCalendarClass']))
        ->assertSuccessful();

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.marksheet.export', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'course_syllabus_module' => $context['module']->id,
            'format' => 'xlsx',
        ]))
        ->assertSuccessful();
});

test('assigned lecturer can open import page for assigned module', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.import', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'course_syllabus_module' => $context['module']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('teaching/classes/Import'));
});

test('lecturer role seeder includes create and export course work', function () {
    $role = \App\Models\Rbac\Role::query()
        ->where('name', \App\Enums\Rbac\RoleEnum::LECTURER->name())
        ->firstOrFail();

    expect($role->hasPermissionTo('create:course-work'))->toBeTrue()
        ->and($role->hasPermissionTo('export:course-work'))->toBeTrue();
});

test('tutor only can open class show with read-only semester modules and export class list', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignClassTutorOnly($context, $staff);
    prepareLecturerCalendar($context);

    $otherModule = CourseSyllabusModule::query()->create([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_id' => $context['module']->course_syllabus_id,
        'academic_year_option_id' => $context['module']->academic_year_option_id,
        'title' => 'Tutor Oversight Module',
        'code' => 'TUT201',
        'duration_in_hours' => 20,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.show', $context['academicCalendarClass']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Show')
            ->where('classDetail.isTutor', true)
            ->has('classDetail.modules', 2)
            ->where('classDetail.modules.0.canManage', false)
            ->where('classDetail.modules.1.canManage', false)
        );

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.class-list.export', $context['academicCalendarClass']))
        ->assertSuccessful();

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.marksheet', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'course_syllabus_module' => $context['module']->id,
        ]))
        ->assertForbidden();

    $this->actingAs($lecturerUser)
        ->get(route('teaching.modules.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('modules', 0));
});

test('tutor only class index shows all semester module codes', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignClassTutorOnly($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    CourseSyllabusModule::query()->create([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_id' => $context['module']->course_syllabus_id,
        'academic_year_option_id' => $context['module']->academic_year_option_id,
        'title' => 'Tutor Oversight Module',
        'code' => 'TUT201',
        'duration_in_hours' => 20,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('classes.0.moduleCodes', 2)
            ->where('classes.0.assignedModuleCodes', [])
            ->where('classes.0.stats.assignedModuleCount', 0)
        );
});

test('syllabus template lecturer does not appear in teaching portal', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignSyllabusTemplateLecturer($context, $staff);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('classes', 0));

    $this->actingAs($lecturerUser)
        ->get(route('teaching.modules.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('modules', 0));

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.show', $context['academicCalendarClass']))
        ->assertForbidden();
});

test('class level module lecturer class show modules are manageable', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.show', $context['academicCalendarClass']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Show')
            ->where('classDetail.isTutor', false)
            ->has('classDetail.modules', 1)
            ->where('classDetail.modules.0.canManage', true)
        );
});

test('assessment windows lock lecturer mark entry after end date', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    $calendarId = prepareLecturerCalendar($context);

    AssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $calendarId,
        'start_date' => now()->subWeeks(2)->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
        'type' => AcademicCalendar::query()->findOrFail($calendarId)->type,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.show', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'academic_calendar_id' => $calendarId,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('classDetail.modules.0.courseWorkLock.allAssessmentTypesLocked', true)
            ->where('classDetail.modules.0.courseWorkLock.hasEditableCourseWork', false)
            ->where('classDetail.modules.0.courseWorkLock.lockedAssessmentTypeIds.0', $context['assessmentType']->id)
        );

    Sanctum::actingAs($lecturerUser);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 68,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
            'academic_calendar_id' => $calendarId,
        ]))
        ->assertStatus(422)
        ->assertJsonPath('errors.0.source.pointer', '/assessmentTypeId');

    expect(CourseWorkMark::query()->count())->toBe(0);
});
