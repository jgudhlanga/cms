<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\HMS\HostelQueryCategoryEnum;
use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Acl\Permission;
use App\Models\Enrolments\ClassList;
use App\Models\HMS\HostelQuery;
use App\Models\Institution\DepartmentCourse;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;

beforeEach(function () {
    enableDashboardModule();
    seedDashboardAcademicCalendar();
});

test('dashboard returns overview metrics for users with overview tab access', function () {
    $user = userWithOverviewDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $confirmedProgram = createVerifiedStudentProgram('OVERVIEW-DASH-CONF-01');
    $confirmedProgram->institutionDepartment->department->update(['is_academic' => true]);
    $confirmedProgram->institutionDepartment->update(['tenant_id' => $user->tenant_id]);
    DepartmentCourse::query()
        ->whereKey($confirmedProgram->department_course_id)
        ->update(['tenant_id' => $user->tenant_id]);
    $confirmedProgram->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
    ]);
    ClassList::query()
        ->where('student_program_id', $confirmedProgram->id)
        ->update([
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'attributes' => [
                'identity_confirmed' => true,
                'disability_confirmed' => true,
                'names_confirmed' => true,
            ],
        ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->has('overviewDashboard')
            ->where('overviewDashboard.summary.passRate', null)
            ->where('overviewDashboard.summary.atRiskStudents', null)
            ->has('overviewDashboard.summary.totalStaff')
            ->has('overviewDashboard.enrolmentFunnel')
            ->where('overviewDashboard.enrolmentFunnel.confirmed', 1)
            ->where('overviewDashboard.enrolmentFunnel.applications', 1)
            ->has('overviewDashboard.academicSnapshot')
            ->has('overviewDashboard.quickInsights')
            ->has('overviewDashboard.enrolmentByDepartment', 1)
            ->where('overviewDashboard.enrolmentByDepartment.0.count', 1)
        );
});

test('dashboard overview reports pass rate and at-risk students from coursework data', function () {
    $context = createCourseWorkJsonApiContext();

    $user = userWithOverviewDashboardPermission();
    $user->update(['tenant_id' => $context['tenant']->id]);

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
            ->where('overviewDashboard.summary.passRate', 100)
            ->where('overviewDashboard.summary.atRiskStudents', null)
            ->has('overviewDashboard.summary.markCompletionRate')
            ->has('overviewDashboard.academicSnapshot.gradeSegments')
            ->has('overviewDashboard.priorityAlerts')
        );
});

test('dashboard overview returns zero students and empty enrolment when no confirmed students exist', function () {
    $user = userWithOverviewDashboardPermission();
    seedDashboardIntakePeriod($user->tenant_id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('overviewDashboard.summary.passRate', null)
            ->where('overviewDashboard.summary.atRiskStudents', null)
            ->where('overviewDashboard.enrolmentByDepartment', [])
            ->where('overviewDashboard.enrolmentFunnel.confirmed', 0)
        );
});

test('dashboard omits overview metrics when overview tab is not visible', function () {
    $user = userWithDashboardPermission('view:dashboards');
    Permission::findOrCreate('view-enrolment:dashboards', 'web');
    $user->givePermissionTo('view-enrolment:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => false,
        'enrolments' => true,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('overviewDashboard', null)
        );
});

test('dashboard overview includes priority alerts from hostel queries', function () {
    $user = userWithOverviewDashboardPermission();
    $tenantId = $user->tenant_id;
    seedDashboardIntakePeriod($tenantId);

    $maleGender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $student = createOverviewDashboardHostelStudent($maleGender, $tenantId);

    HostelQuery::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'category' => HostelQueryCategoryEnum::MAINTENANCE->value,
        'subject' => 'Burst pipe',
        'description' => 'Corridor flooding on level 2',
        'priority' => HostelQueryPriorityEnum::HIGH->value,
        'status' => HostelQueryStatusEnum::OPEN->value,
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->has('overviewDashboard.priorityAlerts')
            ->where('overviewDashboard.priorityAlerts.0.severity', 'critical')
            ->where('overviewDashboard.priorityAlerts.0.message', 'Burst pipe — Corridor flooding on level 2')
        );
});

test('dashboard overview includes provisional enrolment alert', function () {
    $user = userWithOverviewDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $program = createVerifiedStudentProgram('OVERVIEW-DASH-PROV-01');
    $program->institutionDepartment->department->update(['is_academic' => true]);
    $program->institutionDepartment->update(['tenant_id' => $user->tenant_id]);
    $program->update([
        'intake_period_id' => $intakePeriod->id,
        'tenant_id' => $user->tenant_id,
    ]);
    ClassList::query()
        ->where('student_program_id', $program->id)
        ->update([
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'attributes' => [
                'identity_confirmed' => false,
                'disability_confirmed' => false,
                'names_confirmed' => false,
            ],
        ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->has('overviewDashboard.priorityAlerts')
            ->where('overviewDashboard.enrolmentFunnel.provisional', 1)
        );
});

function createOverviewDashboardHostelStudent(Gender $gender, int $tenantId): Student
{
    $title = Title::query()->firstOrCreate(['name' => 'Mr Overview']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Overview']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Overview']);

    $studentUser = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Overview',
        'last_name' => 'Student',
    ]);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT).'OV',
        'date_of_birth' => '2001-01-01',
    ]);
}
