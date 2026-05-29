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

function createStudentProgramForJsonApiTest(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $suffix = uniqid();

    $department = Department::factory()->create(['name' => 'Engineering JSON API '.$suffix]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'json-api-apps-'.$suffix,
        'description' => 'JSON API applications test',
    ]);

    $course = Course::factory()->create(['name' => 'Computer Science JSON '.$suffix]);
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

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time JSON API']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 JSON API',
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

    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr JSON API',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male JSON API',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single JSON API',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID JSON API',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2001-01-01',
        'student_number' => 'JSON-APP-001',
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
        'application_tracking_number' => 'TRK-JSON-001',
    ]);

    return compact('student', 'studentProgram', 'department', 'course', 'workflowStep');
}

test('json api student programs index returns applications for student filter', function () {
    ['student' => $student, 'studentProgram' => $studentProgram, 'department' => $department, 'course' => $course, 'workflowStep' => $workflowStep] = createStudentProgramForJsonApiTest();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['view:student-programs', 'view:students']);
    Sanctum::actingAs($user);

    $response = $this
        ->jsonApi('student-programs')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.students.student-programs.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $studentProgram->id)
        ->assertJsonPath('data.0.attributes.department', $department->name)
        ->assertJsonPath('data.0.attributes.course', $course->name)
        ->assertJsonPath('data.0.attributes.workflowStep', $workflowStep->name)
        ->assertJsonPath('data.0.attributes.intakePeriod', 'Semester 1 JSON API');
});

test('json api student programs index requires student filter', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['view:student-programs', 'view:students']);
    Sanctum::actingAs($user);

    $this
        ->jsonApi('student-programs')
        ->get(route('v1.json.students.student-programs.index'))
        ->assertForbidden();
});

test('json api student programs index is forbidden without permissions', function () {
    ['student' => $student] = createStudentProgramForJsonApiTest();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $this
        ->jsonApi('student-programs')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.students.student-programs.index'))
        ->assertForbidden();
});

test('json api student programs index allows portal student for own records only', function () {
    ['student' => $student, 'studentProgram' => $studentProgram] = createStudentProgramForJsonApiTest();

    $tenant = Tenant::query()->firstOrFail();
    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentProgramDetails:students');
    $student->update(['user_id' => $portalUser->id]);
    Sanctum::actingAs($portalUser);

    $this
        ->jsonApi('student-programs')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.students.student-programs.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $studentProgram->id);

    $otherUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $otherUser->givePermissionTo('manageOwnStudentProgramDetails:students');
    $otherStudent = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $otherUser->id,
        'title_id' => $student->title_id,
        'gender_id' => $student->gender_id,
        'marital_status_id' => $student->marital_status_id,
        'id_type_id' => $student->id_type_id,
        'date_of_birth' => '2002-02-02',
        'student_number' => 'JSON-APP-OTHER-'.uniqid(),
    ]);

    $this
        ->jsonApi('student-programs')
        ->filter(['student' => (string) $otherStudent->id])
        ->get(route('v1.json.students.student-programs.index'))
        ->assertForbidden();
});
