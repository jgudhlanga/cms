<?php

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
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
        ->has('offerLetterIntakePeriodIds')
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

test('root manage user can edit and update any application', function () {
    $program = createVerifiedStudentApplication('PROF-ROOT-'.strtoupper(Str::random(4)));

    $inactiveIntake = IntakePeriod::query()->create([
        'tenant_id' => $program->tenant_id,
        'name' => 'Root Intake '.Str::random(4),
        'start_date' => now()->subYear()->startOfMonth()->toDateString(),
        'end_date' => now()->subYear()->endOfMonth()->toDateString(),
        'is_active' => false,
    ]);

    $program->update(['intake_period_id' => $inactiveIntake->id]);

    $rootUser = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $rootUser->givePermissionTo('root:manage');

    $this->actingAs($rootUser)
        ->get(route('students.program-edit', $program))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('students/EditStudentApplication'));

    $payload = [
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'mode_of_study_id' => $program->mode_of_study_id,
    ];

    $this->actingAs($rootUser)
        ->put(route('students.program-update', $program), $payload)
        ->assertSuccessful();
});

test('updating application syncs linked student enrolment fields', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-SYNC-'.strtoupper(Str::random(4)));
    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['is_active' => true]);

    $admin = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $admin->givePermissionTo('update:student-applications');

    $targetInstitutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $program->tenant_id,
        'department_id' => $program->institutionDepartment->department_id,
        'department_code' => 'SYNC-'.strtoupper(Str::random(6)),
        'description' => 'Sync target department',
    ]);

    $targetDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $program->tenant_id,
        'institution_department_id' => $targetInstitutionDepartment->id,
        'level_id' => $program->departmentLevel->level_id,
    ]);

    $targetDepartmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $program->tenant_id,
        'institution_department_id' => $targetInstitutionDepartment->id,
        'course_id' => $program->departmentCourse->course_id,
        'show_on_current_application_period' => true,
    ]);

    $targetModeOfStudy = ModeOfStudy::factory()->create();
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Sync Year Option',
        'slug' => 'sync-year-option-'.strtolower(Str::random(6)),
    ]);
    $academicCalendar = AcademicCalendar::query()->create([
        'calendar_year' => (string) now()->year,
        'type' => 'term',
        'opening_date' => now()->startOfMonth()->toDateString(),
        'closing_date' => now()->endOfMonth()->toDateString(),
    ]);
    $studentEnrolmentStatus = StudentEnrolmentStatus::query()->create([
        'name' => 'Active Sync Status',
        'slug' => 'active-sync-status-'.strtolower(Str::random(6)),
    ]);

    $studentEnrolment = StudentEnrolment::query()->create([
        'student_id' => $program->student_id,
        'student_application_id' => $program->id,
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $academicCalendar->id,
        'mode_of_study_id' => $program->mode_of_study_id,
        'student_enrolment_status_id' => $studentEnrolmentStatus->id,
    ]);

    $payload = [
        'institution_department_id' => $targetInstitutionDepartment->id,
        'department_level_id' => $targetDepartmentLevel->id,
        'department_course_id' => $targetDepartmentCourse->id,
        'mode_of_study_id' => $targetModeOfStudy->id,
    ];

    $this->actingAs($admin)
        ->put(route('students.program-update', $program), $payload)
        ->assertSuccessful();

    $program->refresh();
    $studentEnrolment->refresh();

    expect($program->institution_department_id)->toBe($targetInstitutionDepartment->id)
        ->and($program->department_level_id)->toBe($targetDepartmentLevel->id)
        ->and($program->department_course_id)->toBe($targetDepartmentCourse->id)
        ->and($program->mode_of_study_id)->toBe($targetModeOfStudy->id)
        ->and($studentEnrolment->institution_department_id)->toBe($targetInstitutionDepartment->id)
        ->and($studentEnrolment->department_level_id)->toBe($targetDepartmentLevel->id)
        ->and($studentEnrolment->department_course_id)->toBe($targetDepartmentCourse->id)
        ->and($studentEnrolment->mode_of_study_id)->toBe($targetModeOfStudy->id);
});

test('updating application without linked enrolment does not create student enrolment', function () {
    $program = createReviewStudentApplicationForConsolidation('PROF-NOENR-'.strtoupper(Str::random(4)));
    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['is_active' => true]);

    $admin = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $admin->givePermissionTo('update:student-applications');

    $targetModeOfStudy = ModeOfStudy::factory()->create();

    $payload = [
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'mode_of_study_id' => $targetModeOfStudy->id,
    ];

    expect(StudentEnrolment::query()->where('student_application_id', $program->id)->count())->toBe(0);

    $this->actingAs($admin)
        ->put(route('students.program-update', $program), $payload)
        ->assertSuccessful();

    expect(StudentEnrolment::query()->where('student_application_id', $program->id)->count())->toBe(0);
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
