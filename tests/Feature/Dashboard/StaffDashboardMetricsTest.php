<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;

beforeEach(function () {
    enableDashboardModule();
    seedDashboardTestRoles();
    seedDashboardAcademicCalendar();
});

test('dashboard returns staff metrics for users with staff tab access', function () {
    $user = userWithStaffDashboardPermission();
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    $confirmedProgram = createVerifiedStudentApplication('STAFF-DASH-CONF-01');
    $confirmedProgram->institutionDepartment->department->update(['is_academic' => true]);
    $confirmedProgram->update(['intake_period_id' => $intakePeriod->id]);
    ClassList::query()
        ->where('student_application_id', $confirmedProgram->id)
        ->update([
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'attributes' => [
                'identity_confirmed' => true,
                'disability_confirmed' => true,
                'names_confirmed' => true,
            ],
        ]);

    $lecturerUser = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $lecturerUser->assignRole(Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail());

    $adminUser = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $adminUser->assignRole(Role::query()->where('name', RoleEnum::HR_OFFICER->name())->firstOrFail());

    $maleGender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $fullTimeEmployment = EmploymentType::query()->firstOrCreate([
        'name' => EmploymentTypeEnum::FULL_TIME->value,
    ], [
        'description' => EmploymentTypeEnum::FULL_TIME->description(),
    ]);

    $lecturerStaff = Staff::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $lecturerUser->id,
        'title_id' => $title->id,
        'gender_id' => $maleGender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $fullTimeEmployment->id,
        'employee_number' => 'STAFF-LECT-001',
    ]);
    $lecturerStaff->institutionDepartments()->attach($confirmedProgram->institution_department_id);

    Staff::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $adminUser->id,
        'title_id' => $title->id,
        'gender_id' => $maleGender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $fullTimeEmployment->id,
        'employee_number' => 'STAFF-ADMIN-001',
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('staffDashboard.summary.totalStaff', 2)
            ->where('staffDashboard.summary.academicCount', 1)
            ->where('staffDashboard.summary.adminCount', 1)
            ->where('staffDashboard.summary.presentToday', null)
            ->where('staffDashboard.summary.onLeaveToday', null)
            ->where('staffDashboard.summary.unfilledSessions', null)
            ->where('staffDashboard.categoryBreakdown.fullTimeLecturers', 1)
            ->where('staffDashboard.categoryBreakdown.partTimeLecturers', 0)
            ->where('staffDashboard.categoryBreakdown.postgradQualified', null)
            ->where('staffDashboard.categoryBreakdown.onStudyLeave', null)
            ->where('staffDashboard.academicGenderSplit.male', 1)
            ->where('staffDashboard.academicGenderSplit.female', 0)
            ->where('staffDashboard.attendanceTrend', null)
            ->has('staffDashboard.lecturerRatios', 1)
            ->where('staffDashboard.lecturerRatios.0.studentCount', 1)
            ->where('staffDashboard.lecturerRatios.0.lecturerCount', 1)
            ->where('staffDashboard.lecturerRatios.0.ratioLabel', '1:1')
            ->where('staffDashboard.overCapacityRooms', [])
        );
});

test('dashboard does not return staff metrics when staff tab is disabled', function () {
    $user = userWithDashboardPermission('view:dashboards');
    Permission::findOrCreate('view-staff:dashboards', 'web');
    $user->givePermissionTo('view-staff:dashboards');

    enableDashboardModule([
        'overview' => true,
        'academic' => false,
        'enrolments' => false,
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
            ->where('staffDashboard', null)
        );
});
