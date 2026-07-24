<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;

beforeEach(function () {
    enableDashboardModule();
    $this->seed(ClassMetaDataTypeSeeder::class);
});

test('dashboard returns academic metrics for users with academic tab access', function () {
    $context = createCourseWorkJsonApiContext();

    $user = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    Permission::findOrCreate('view-academic:dashboards', 'web');
    $user->givePermissionTo('view-academic:dashboards');

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
        'mark' => 45,
    ]);

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);
    seedDashboardIntakePeriod($context['tenant']->id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicDashboard.summary.passRate', 100)
            ->where('academicDashboard.summary.failRate', 0)
            ->where('academicDashboard.summary.distinctionRate', 100)
            ->where('academicDashboard.summary.markCompletionRate', 100)
            ->where('academicDashboard.summary.probationCount', null)
            ->where('academicDashboard.summary.passRateTrend', null)
            ->where('academicDashboard.courseWorkStatus.expectedModuleResults', 1)
            ->where('academicDashboard.courseWorkStatus.completeCount', 1)
            ->where('academicDashboard.courseWorkStatus.incompleteCount', 0)
            ->where('academicDashboard.courseWorkStatus.outstandingCount', 0)
            ->where('academicDashboard.attachmentStatus', null)
            ->has('academicDashboard.gradeDistribution.segments', 1)
            ->where('academicDashboard.gradeDistribution.segments.0.key', 'distinction')
            ->has('academicDashboard.passRateByDepartment', 1)
            ->has('academicDashboard.moduleFailureHotspots', 1)
        );
});

test('dashboard reports ojet students as placed on industrial attachment', function () {
    $context = createCourseWorkJsonApiContext();

    $user = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    Permission::findOrCreate('view-academic:dashboards', 'web');
    $user->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $ojetMode = ModeOfStudy::query()->firstOrCreate(['name' => ModeOfStudyEnum::OJET->value]);
    $context['studentEnrolment']->update(['mode_of_study_id' => $ojetMode->id]);

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'calendar_year' => '2026',
    ]);
    seedDashboardIntakePeriod($context['tenant']->id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicDashboard.attachmentStatus.total', 1)
            ->where('academicDashboard.attachmentTotal', 1)
            ->where('academicDashboard.attachmentStatus.placed', 1)
            ->where('academicDashboard.attachmentStatus.awaiting', 0)
            ->where('academicDashboard.attachmentStatus.exempt', 0)
            ->where('academicDashboard.attachmentStatus.calendarYear', '2026')
            ->has('academicDashboard.attachmentStatus.segments', 1)
            ->where('academicDashboard.attachmentStatus.segments.0.key', 'placed')
            ->where('academicDashboard.attachmentStatus.segments.0.percent', 100)
        );
});

test('dashboard counts distinct ojet students across calendar year semesters', function () {
    $context = createCourseWorkJsonApiContext();

    $user = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    Permission::findOrCreate('view-academic:dashboards', 'web');
    $user->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $ojetMode = ModeOfStudy::query()->firstOrCreate(['name' => ModeOfStudyEnum::OJET->value]);
    $context['studentEnrolment']->update(['mode_of_study_id' => $ojetMode->id]);

    $firstCalendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($firstCalendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'calendar_year' => '2026',
        'opening_date' => now()->subMonths(2)->toDateString(),
        'closing_date' => now()->subMonth()->toDateString(),
    ]);

    $secondCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => now()->subDays(10)->toDateString(),
        'closing_date' => now()->addMonths(5)->toDateString(),
    ]);

    $enrolmentStatus = StudentEnrolmentStatus::query()->firstOrFail();

    $secondEnrolment = StudentEnrolment::query()->create([
        'student_id' => $context['studentEnrolment']->student_id,
        'student_application_id' => $context['studentEnrolment']->student_application_id,
        'institution_department_id' => $context['studentEnrolment']->institution_department_id,
        'department_level_id' => $context['studentEnrolment']->department_level_id,
        'department_course_id' => $context['studentEnrolment']->department_course_id,
        'academic_year_option_id' => $context['studentEnrolment']->academic_year_option_id,
        'academic_calendar_id' => $secondCalendar->id,
        'mode_of_study_id' => $ojetMode->id,
        'student_enrolment_status_id' => $enrolmentStatus->id,
    ]);

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'student_enrolment_id' => $secondEnrolment->id,
    ]);

    seedDashboardIntakePeriod($context['tenant']->id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user, $secondCalendar->id))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicDashboard.attachmentTotal', 1)
            ->where('academicDashboard.attachmentStatus.total', 1)
        );
});

test('dashboard reports incomplete and outstanding marks when coursework is not fully captured', function () {
    $context = createCourseWorkJsonApiContext();

    $user = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    Permission::findOrCreate('view-academic:dashboards', 'web');
    $user->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);
    seedDashboardIntakePeriod($context['tenant']->id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicDashboard.summary.passRate', null)
            ->where('academicDashboard.summary.markCompletionRate', 0)
            ->where('academicDashboard.courseWorkStatus.expectedModuleResults', 1)
            ->where('academicDashboard.courseWorkStatus.incompleteCount', 1)
            ->where('academicDashboard.courseWorkStatus.outstandingCount', 1)
            ->has('academicDashboard.missingMarksByModule', 1)
            ->where('academicDashboard.missingMarksByModule.0.incomplete', 1)
        );
});

test('dashboard attributes incomplete marks to assigned lecturer', function () {
    $context = createCourseWorkJsonApiContext();

    $user = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    Permission::findOrCreate('view-academic:dashboards', 'web');
    $user->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    seedDashboardTestRoles();

    $lecturerUser = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Jane',
        'last_name' => 'Lecturer',
    ]);
    $lecturerUser->assignRole(Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail());

    $lecturerStaff = Staff::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $lecturerUser->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Ms'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Female'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'employment_type_id' => EmploymentType::query()->firstOrCreate([
            'name' => EmploymentTypeEnum::FULL_TIME->value,
        ], [
            'description' => EmploymentTypeEnum::FULL_TIME->description(),
        ])->id,
        'employee_number' => 'LECT-DASH-001',
    ]);

    $lecturerTypeId = ClassMetaDataType::query()
        ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
        ->value('id');

    AcademicCalendarClassMetaData::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'staff_id' => $lecturerStaff->id,
        'class_metadata_type_id' => $lecturerTypeId,
    ]);

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);
    seedDashboardIntakePeriod($context['tenant']->id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->has('academicDashboard.lecturerMarkingStats', 1)
            ->where('academicDashboard.lecturerMarkingStats.0.staffId', $lecturerStaff->id)
            ->where('academicDashboard.lecturerMarkingStats.0.lecturerName', 'Jane Lecturer')
            ->where('academicDashboard.lecturerMarkingStats.0.incomplete', 1)
            ->where('academicDashboard.lecturerMarkingStats.0.classesCount', 1)
        );
});

test('dashboard does not return academic metrics when academic tab is disabled', function () {
    $user = userWithDashboardPermission('view:dashboards');
    Permission::findOrCreate('view-academic:dashboards', 'web');
    $user->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => true,
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    seedDashboardAcademicCalendar();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicDashboard', null)
        );
});

test('dashboard academic metrics are empty when no enrolled students exist for coursework', function () {
    $user = userWithAcademicDashboardPermission();
    seedDashboardAcademicCalendar();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('academicDashboard.summary.passRate', null)
            ->where('academicDashboard.summary.failRate', null)
            ->where('academicDashboard.summary.distinctionRate', null)
            ->where('academicDashboard.summary.markCompletionRate', null)
            ->where('academicDashboard.courseWorkStatus.expectedModuleResults', 0)
            ->where('academicDashboard.gradeDistribution.segments', [])
            ->where('academicDashboard.passRateByDepartment', [])
            ->where('academicDashboard.moduleFailureHotspots', [])
            ->where('academicDashboard.missingMarksByDepartment', [])
            ->where('academicDashboard.lecturerMarkingStats', [])
            ->where('academicDashboard.attachmentStatus', null)
            ->where('academicDashboard.attachmentTotal', null)
        );
});
