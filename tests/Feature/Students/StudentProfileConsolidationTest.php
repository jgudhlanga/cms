<?php

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Students\StudentApplication;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Str;

test('student show page accepts from query parameter', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-FROM-'.strtoupper(Str::random(4)));
    $student = $program->student;

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $response = $this->actingAs($admin)->get(route('students.show', [
        'student' => $student,
        'from' => 'users',
        'return' => '/users?search=test',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('students/Show'));
});

test('students profile route redirects to student show page', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-REDIR-'.strtoupper(Str::random(4)));
    $student = $program->student;
    $user = $student->user;

    $admin = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $admin->givePermissionTo('viewAny:students');

    $response = $this->actingAs($admin)->get(route('students.profile', $user));

    $response->assertRedirect(route('students.show', $student));
});

test('student show page includes active intake period ids', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-SHOW-'.strtoupper(Str::random(4)));
    $student = $program->student;

    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['is_active' => true]);

    $admin = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $response = $this->actingAs($admin)->get(route('students.show', $student));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('students/Show')
        ->has('activeIntakePeriodIds')
        ->where('student.id', $student->id));
});

test('staff with update permission can update active intake application', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-UPD-'.strtoupper(Str::random(4)));
    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['is_active' => true]);

    $admin = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $admin->givePermissionTo('update:student-applications');

    $payload = [
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'mode_of_study_id' => $program->mode_of_study_id,
    ];

    $response = $this->actingAs($admin)->put(route('students.program-update', $program), $payload);

    $response->assertSuccessful();
});

test('staff without update permission cannot update application', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-403-'.strtoupper(Str::random(4)));
    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['is_active' => true]);

    $staff = User::factory()->create(['tenant_id' => $program->tenant_id]);

    $payload = [
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'mode_of_study_id' => $program->mode_of_study_id,
    ];

    $this->actingAs($staff)
        ->put(route('students.program-update', $program), $payload)
        ->assertForbidden();
});

test('staff cannot update application for inactive intake period', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-INACT-'.strtoupper(Str::random(4)));

    $inactiveIntake = IntakePeriod::query()->create([
        'tenant_id' => $program->tenant_id,
        'name' => 'Past Intake '.Str::random(4),
        'start_date' => now()->subYear()->startOfMonth()->toDateString(),
        'end_date' => now()->subYear()->endOfMonth()->toDateString(),
        'is_active' => false,
    ]);

    $program->update(['intake_period_id' => $inactiveIntake->id]);

    $admin = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $admin->givePermissionTo('update:student-applications');

    $payload = [
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'mode_of_study_id' => $program->mode_of_study_id,
    ];

    $this->actingAs($admin)
        ->put(route('students.program-update', $program), $payload)
        ->assertForbidden();
});

test('staff cannot update accepted application', function () {
    $program = createVerifiedStudentApplication('PROF-ACC-'.strtoupper(Str::random(4)));
    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['is_active' => true]);

    $admin = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $admin->givePermissionTo('update:student-applications');

    $payload = [
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'mode_of_study_id' => $program->mode_of_study_id,
    ];

    $this->actingAs($admin)
        ->put(route('students.program-update', $program), $payload)
        ->assertForbidden();
});

test('portal student cannot edit another students application', function () {
    $ownerProgram = createReviewStudentApplicationForConsolidation('PROF-OWN-'.strtoupper(Str::random(4)));
    $otherProgram = createReviewStudentApplicationForConsolidation('PROF-OTH-'.strtoupper(Str::random(4)));

    $intruder = $otherProgram->student->user;
    $intruder->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $this->actingAs($intruder)
        ->get(route('portal.application.edit', $ownerProgram))
        ->assertForbidden();

    $payload = [
        'department_id' => $ownerProgram->institution_department_id,
        'level_id' => $ownerProgram->department_level_id,
        'course_id' => $ownerProgram->department_course_id,
        'mode_of_study_id' => $ownerProgram->mode_of_study_id,
    ];

    $this->actingAs($intruder)
        ->put(route('portal.application.update', $ownerProgram), $payload)
        ->assertForbidden();
});

test('users edit redirects student users to student show page', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-UED-'.strtoupper(Str::random(4)));
    $studentUser = $program->student->user;

    $admin = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $admin->givePermissionTo('view:users');

    $this->actingAs($admin)
        ->get(route('users.edit', $studentUser))
        ->assertRedirect(route('students.show', $program->student));
});

function createReviewStudentApplicationForConsolidation(string $studentNumber): StudentApplication
{
    $program = createVerifiedStudentApplication($studentNumber);
    $reviewStep = resolveDepartmentApplicationStep($program, WorkflowStepEnum::REVIEW);

    $program->update([
        'department_application_step_id' => $reviewStep->id,
    ]);

    return $program->fresh();
}
