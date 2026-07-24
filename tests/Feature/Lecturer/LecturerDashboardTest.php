<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
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

test('unauthorized users cannot access academic dashboard tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('lecturer-only user sees academic tab with teaching metrics and null attendance', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $context['assessmentType']->update(['weight_percent' => 100]);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 80,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->where('teachingDashboard.attendance', null)
            ->where('teachingDashboard.summary.passRate', 100)
            ->where('teachingDashboard.summary.averageMark', 60)
            ->where('teachingDashboard.summary.modulesCount', 1)
            ->has('teachingDashboard.topPerformingStudents', 1)
            ->where('overviewDashboard', null)
            ->where('academicDashboard', null)
        );
});

test('teaching dashboard shows null metrics when no marks exist', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->where('teachingDashboard.attendance', null)
            ->where('teachingDashboard.summary.passRate', null)
            ->where('teachingDashboard.summary.averageMark', null)
            ->where('teachingDashboard.summary.missingCourseWorkCount', 1)
            ->has('teachingDashboard.missingCourseWork', 1)
        );
});

test('dual permission user sees single academic tab with both dashboards', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    Permission::findOrCreate('view-academic:dashboards', 'web');
    $lecturerUser->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->where('teachingDashboard', fn ($value) => $value !== null)
            ->where('academicDashboard', fn ($value) => $value !== null)
        );
});

test('teaching classes index only returns assigned classes', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    $otherClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $context['tenant']->id,
        'class_config_id' => $context['academicCalendarClass']->class_config_id,
        'name' => 'OTHER-CLASS-1',
        'description' => null,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Index')
            ->has('summary')
            ->where('summary.classCount', 1)
            ->has('classes', 1)
            ->where('classes.0.academicCalendarClassId', $context['academicCalendarClass']->id)
            ->where('classes.0.name', $context['academicCalendarClass']->name)
            ->where('classes.0.academicCalendarClassId', fn ($id) => (int) $id !== (int) $otherClass->id)
            ->has('classes.0.genderCounts')
            ->has('classes.0.studentCount')
            ->has('classes.0.moduleCodes', 1)
            ->where('classes.0.moduleCodes.0', $context['module']->code)
            ->has('classes.0.assignedModuleCodes', 1)
            ->has('classes.0.assessmentWindows')
            ->has('classes.0.stats')
        );
});

test('teaching classes index includes assessment windows for class mode of study', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    $calendarId = prepareLecturerCalendar($context);

    AssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $calendarId,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Index')
            ->has('classes.0.assessmentWindows', 1)
            ->where('classes.0.assessmentWindows.0.assessmentTypeName', $context['assessmentType']->name)
            ->where('classes.0.assessmentWindows.0.isOpen', true)
            ->where('summary.openAssessmentWindowCount', 1)
        );
});

test('module lecturer class index only shows assigned module codes', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    $calendarId = prepareLecturerCalendar($context);

    CourseSyllabusModule::query()->create([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_id' => $context['module']->course_syllabus_id,
        'academic_year_option_id' => $context['module']->academic_year_option_id,
        'title' => 'Unassigned Module',
        'code' => 'UNAS1',
        'duration_in_hours' => 20,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('classes.0.moduleCodes', 1)
            ->where('classes.0.moduleCodes.0', $context['module']->code)
        );
});

test('teaching modules index only returns assigned modules', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    $calendarId = prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.modules.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/modules/Index')
            ->has('modules', 1)
            ->where('modules.0.id', $context['module']->id)
            ->where('modules.0.title', $context['module']->title)
        );
});

test('lecturer role seeder receives lecturer portal permissions', function () {
    $role = Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail();

    expect($role->hasPermissionTo('view:lecturer-dashboard'))->toBeTrue()
        ->and($role->hasPermissionTo('view:lecturer-classes'))->toBeTrue()
        ->and($role->hasPermissionTo('view:lecturer-modules'))->toBeTrue()
        ->and($role->hasPermissionTo('view:course-work'))->toBeTrue();
});

test('teaching dashboard priority alerts include applicable assessment calendars', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    AssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $calendarId,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->has('teachingDashboard.priorityAlerts')
            ->where('teachingDashboard.priorityAlerts.0.kind', 'assessment_calendar')
            ->where('teachingDashboard.priorityAlerts.0.severity', 'critical')
            ->where('teachingDashboard.priorityAlerts.0.daysRemaining', 2)
            ->where('teachingDashboard.priorityAlerts.0.assessmentTypeName', $context['assessmentType']->name)
        );
});

test('legacy lecturer dashboard url redirects to shared dashboard', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser] = createLecturerUserWithStaff($context);

    $this->actingAs($lecturerUser)
        ->get('/lecturer/dashboard')
        ->assertRedirect('/dashboard');
});

test('home redirects staff to shared dashboard', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser] = createLecturerUserWithStaff($context);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
