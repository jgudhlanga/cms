<?php

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function createPortalDashboardStudent(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $suffix = uniqid();

    $department = Department::factory()->create(['name' => 'Dashboard Dept '.$suffix]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'dash-dept-'.$suffix,
        'description' => 'Dashboard test department',
    ]);

    $course = Course::factory()->create(['name' => 'Dashboard Course '.$suffix]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'National Certificate']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Dashboard']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 Dashboard',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $workflowStep = WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::REVIEW->slug()],
        [
            'name' => WorkflowStepEnum::REVIEW->name(),
            'description' => WorkflowStepEnum::REVIEW->description(),
            'position' => WorkflowStepEnum::REVIEW->position(),
        ],
    );

    $departmentApplicationStep = DepartmentApplicationStep::query()->firstOrCreate(
        [
            'tenant_id' => $tenant->id,
            'institution_department_id' => $institutionDepartment->id,
            'workflow_step_id' => $workflowStep->id,
        ],
        ['position' => $workflowStep->position],
    );

    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo(['viewOwnDashboard:students', 'manageOwnStudentProgramDetails:students']);

    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $portalUser->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Dashboard',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Dashboard',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Dashboard',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Dashboard',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2001-01-01',
        'student_number' => 'DASH-'.uniqid(),
    ]);

    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'department_application_step_id' => $departmentApplicationStep->id,
        'application_tracking_number' => 'TRK-DASH-001',
    ]);

    return compact('portalUser', 'student', 'studentProgram');
}

test('json api student portal dashboard stats returns meta for authenticated student', function () {
    ['portalUser' => $portalUser, 'studentProgram' => $studentProgram] = createPortalDashboardStudent();

    Sanctum::actingAs($portalUser);

    $response = $this
        ->jsonApi('student-programs')
        ->get(route('v1.json.student-programs.dashboardStats'));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('meta.applicationCount', 1)
        ->assertJsonPath('meta.pendingApplicationCount', 1)
        ->assertJsonPath('meta.oLevelSubjectCount', 0)
        ->assertJsonStructure([
            'meta' => [
                'activeModuleCount',
                'totalModuleHours',
                'averageCourseWorkScore',
                'oLevelSubjectCount',
                'applicationCount',
                'pendingApplicationCount',
                'modules',
                'activities',
                'notices',
                'calendarType',
                'currentTerm',
                'nextTerm',
            ],
        ]);

    expect($response->json('meta.modules'))->toBeArray();
    expect($response->json('meta.activities'))->toBeArray();
    expect($response->json('meta.financial'))->toBeNull();
});

test('json api student portal dashboard stats is forbidden without student profile', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('viewOwnDashboard:students');
    Sanctum::actingAs($user);

    $this
        ->jsonApi('student-programs')
        ->get(route('v1.json.student-programs.dashboardStats'))
        ->assertForbidden();
});

test('json api student portal dashboard stats is forbidden without dashboard permission', function () {
    ['student' => $student] = createPortalDashboardStudent();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $student->update(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    $this
        ->jsonApi('student-programs')
        ->get(route('v1.json.student-programs.dashboardStats'))
        ->assertForbidden();
});

test('json api student portal dashboard stats includes financial summary when permitted', function () {
    ['portalUser' => $portalUser] = createPortalDashboardStudent();
    $portalUser->givePermissionTo('manageOwnStudentFinancialDetails:students');
    Sanctum::actingAs($portalUser);

    $response = $this
        ->jsonApi('student-programs')
        ->get(route('v1.json.student-programs.dashboardStats'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'meta' => [
                'financial' => [
                    'paidPercent',
                    'outstandingBalance',
                    'totalInvoiced',
                    'totalPayments',
                ],
            ],
        ]);
});

test('json api student portal dashboard stats requires authentication', function () {
    $this
        ->jsonApi('student-programs')
        ->get(route('v1.json.student-programs.dashboardStats'))
        ->assertUnauthorized();
});
