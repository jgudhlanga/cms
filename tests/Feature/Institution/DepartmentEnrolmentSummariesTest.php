<?php

use App\Enums\Shared\TenantEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Rbac\Permission;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

function makeDepartmentEnrolmentSummariesUser(): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    Permission::findOrCreate('view:department-metadata', 'web');
    $user->givePermissionTo('view:department-metadata');

    return $user;
}

function createDepartmentEnrolmentApplication(array $seeded, string $suffix): StudentApplication
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $workflowStep = WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::REVIEW->slug()],
        [
            'name' => WorkflowStepEnum::REVIEW->name(),
            'description' => WorkflowStepEnum::REVIEW->description(),
            'position' => WorkflowStepEnum::REVIEW->position(),
        ],
    );

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Enrol',
        'last_name' => $suffix,
    ]);

    $student = Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'], ['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'], ['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'], ['title' => 'Single'])->id,
        'id_type_id' => IdType::query()->firstOrCreate(['name' => 'National ID'], ['name' => 'National ID'])->id,
        'date_of_birth' => '2001-01-01',
        'student_number' => 'ENR-'.$suffix,
    ]);

    return StudentApplication::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'institution_department_id' => $seeded['departmentId'],
        'department_level_id' => $seeded['departmentLevelId'],
        'department_course_id' => $seeded['courseId'],
        'intake_period_id' => $seeded['intakeId'],
        'mode_of_study_id' => $seeded['modeId'],
        'workflow_step_id' => $workflowStep->id,
        'application_tracking_number' => 'TRK-ENR-'.$suffix,
    ]);
}

it('returns json api enrolment summaries with mode totals and course level counts', function () {
    $seeded = seedGuestRegistrationProgramme();
    createDepartmentEnrolmentApplication($seeded, uniqid());
    createDepartmentEnrolmentApplication($seeded, uniqid());

    $user = makeDepartmentEnrolmentSummariesUser();
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.department-metadata.enrolments', [
        'institution_department' => $seeded['departmentId'],
        'intake_period_id' => $seeded['intakeId'],
        'mode_of_study_id' => $seeded['modeId'],
    ]));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'departmentCourseId',
                        'courseName',
                        'departmentLevelId',
                        'levelName',
                        'enrolmentsCount',
                        'modeOfStudyId',
                    ],
                ],
            ],
            'meta' => [
                'modeTotals' => [
                    '*' => ['modeOfStudyId', 'count'],
                ],
            ],
        ]);

    expect($response->json('data.0.type'))->toBe('department-enrolment-summaries')
        ->and($response->json('data.0.attributes.enrolmentsCount'))->toBeGreaterThanOrEqual(2)
        ->and(collect($response->json('meta.modeTotals'))->firstWhere('modeOfStudyId', $seeded['modeId'])['count'] ?? 0)
        ->toBeGreaterThanOrEqual(2);
});

it('returns mode totals without course rows when mode is omitted', function () {
    $seeded = seedGuestRegistrationProgramme();
    createDepartmentEnrolmentApplication($seeded, uniqid());

    $user = makeDepartmentEnrolmentSummariesUser();
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.department-metadata.enrolments', [
        'institution_department' => $seeded['departmentId'],
        'intake_period_id' => $seeded['intakeId'],
    ]));

    $response->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonStructure(['meta' => ['modeTotals']]);

    expect(collect($response->json('meta.modeTotals'))->firstWhere('modeOfStudyId', $seeded['modeId'])['count'] ?? 0)
        ->toBeGreaterThanOrEqual(1);
});
