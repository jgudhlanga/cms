<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Finance\PastelLinkedStudent;
use App\Models\Institution\IntakePeriod;
use App\Models\Rbac\Role;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use App\Support\Rbac\PermissionRegistry;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

function activateIntakePeriodForStatusTest(StudentApplication $application): IntakePeriod
{
    $intakePeriod = $application->intakePeriod;

    $intakePeriod->update([
        'is_active' => true,
        'is_continuous' => false,
        'status' => 'open',
    ]);

    return $intakePeriod->refresh();
}

function seedWorkflowStepsForStatusTest(): void
{
    foreach (WorkflowStepEnum::cases() as $step) {
        resolveWorkflowStep($step);
    }
}

function removeApplicationLevelForStatusTest(StudentApplication $application): void
{
    $application->loadMissing('departmentLevel.level');
    $application->departmentLevel?->level?->delete();
    $application->unsetRelation('departmentLevel');
}

function createStudentStatusManager(Student $student): User
{
    $user = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $user->givePermissionTo(['view:students', 'viewAny:students', 'change-student-status:students']);

    return $user;
}

function createStudentNumberManager(Student $student): User
{
    $user = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $user->givePermissionTo(['view:students', 'viewAny:students', 'change-student-number:students']);

    return $user;
}

it('registers the change student number and status permissions', function (): void {
    expect(PermissionRegistry::allValues())
        ->toContain('change-student-number:students')
        ->toContain('change-student-status:students');
});

it('grants the new student permissions to super user only', function (): void {
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();

    $superUser = Role::query()->where('name', RoleEnum::SUPER_USER->name())->firstOrFail();

    expect($superUser->permissions->pluck('name')->all())
        ->toContain('change-student-number:students')
        ->toContain('change-student-status:students');

    $otherRoleNames = [
        RoleEnum::PRINCIPAL->name(),
        RoleEnum::REGISTRAR->name(),
        RoleEnum::REGISTRY_OFFICER->name(),
        RoleEnum::HEAD_OF_DEPARTMENT->name(),
        RoleEnum::IT_SUPPORT_TECHNICIAN->name(),
    ];

    foreach ($otherRoleNames as $roleName) {
        $permissions = Role::query()->where('name', $roleName)->firstOrFail()->permissions->pluck('name')->all();

        expect($permissions)
            ->not->toContain('change-student-number:students')
            ->not->toContain('change-student-status:students');
    }
});

it('exposes the application workflow status for enrolled students with an unknown exam status', function (): void {
    $application = createVerifiedStudentApplication('HDR-'.strtoupper(Str::random(4)));
    $student = $application->student;

    $enrolledStep = resolveWorkflowStep(WorkflowStepEnum::ENROLLED);
    $application->update(['workflow_step_id' => $enrolledStep->id]);

    $unknownStatus = StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Unknown'],
        ['description' => 'Exam results not recorded yet.'],
    );

    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $unknownStatus->id,
    ]);

    $viewer = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $viewer->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($viewer)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.profileContext', 'enrolled')
            ->where('student.attributes.applicationStatus', WorkflowStepEnum::ENROLLED->name()));
});

it('flags applications without a level as invalid', function (): void {
    $application = createVerifiedStudentApplication('LVL-'.strtoupper(Str::random(4)));
    $student = $application->student;

    removeApplicationLevelForStatusTest($application);

    $viewer = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $viewer->givePermissionTo(['view:students', 'viewAny:students', 'viewAny:student-applications']);

    $this->actingAs($viewer)
        ->getJson(route('v1.students.programs', $student))
        ->assertOk()
        ->assertJsonPath('0.attributes.missingLevel', true)
        ->assertJsonPath('0.attributes.isInvalid', true);
});

it('updates the student number and records the reason on the activity trail', function (): void {
    $application = createVerifiedStudentApplication('NUM-OLD-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $manager = createStudentNumberManager($student);

    $this->actingAs($manager)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => '26abc0001hp',
            'reason' => 'Registry corrected a mistyped student number.',
        ])
        ->assertRedirect();

    expect($student->fresh()->student_number)->toBe('26ABC0001HP');

    $activity = Activity::query()
        ->where('subject_type', $student->getMorphClass())
        ->where('subject_id', $student->id)
        ->where('event', 'student-number-changed')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['new_student_number'])->toBe('26ABC0001HP')
        ->and($activity->properties['reason'])->toBe('Registry corrected a mistyped student number.')
        ->and($activity->causer_id)->toBe($manager->id);
});

it('rejects a student number already used by another student', function (): void {
    $application = createVerifiedStudentApplication('NUM-A-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $other = createVerifiedStudentApplication('NUM-TAKEN-0001')->student;
    $manager = createStudentNumberManager($student);

    $this->actingAs($manager)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => $other->student_number,
            'reason' => 'Attempting to reuse an existing student number.',
        ])
        ->assertSessionHasErrors('student_number');

    expect($student->fresh()->student_number)->not->toBe($other->student_number);
});

it('rejects a student number held by an archived student', function (): void {
    $application = createVerifiedStudentApplication('NUM-B-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $archived = createVerifiedStudentApplication('NUM-ARCH-0001')->student;
    $archivedNumber = (string) $archived->student_number;
    $archived->delete();

    $manager = createStudentNumberManager($student);

    $this->actingAs($manager)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => $archivedNumber,
            'reason' => 'Attempting to reuse an archived student number.',
        ])
        ->assertSessionHasErrors('student_number');

    expect($student->fresh()->student_number)->not->toBe($archivedNumber);
});

it('rejects a student number linked to another student in the pastel register', function (): void {
    $application = createVerifiedStudentApplication('NUM-C-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $other = createVerifiedStudentApplication('NUM-D-'.strtoupper(Str::random(4)))->student;

    PastelLinkedStudent::query()->create([
        'tenant_id' => $other->tenant_id,
        'student_id' => $other->id,
        'student_number' => 'PASTEL-0001',
        'linked_at' => now(),
    ]);

    $manager = createStudentNumberManager($student);

    $this->actingAs($manager)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => 'PASTEL-0001',
            'reason' => 'Attempting to reuse a finance-linked student number.',
        ])
        ->assertSessionHasErrors('student_number');
});

it('keeps the pastel register in step when the student number changes', function (): void {
    $application = createVerifiedStudentApplication('NUM-E-'.strtoupper(Str::random(4)));
    $student = $application->student;

    $linked = PastelLinkedStudent::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'student_number' => (string) $student->student_number,
        'linked_at' => now(),
    ]);

    $manager = createStudentNumberManager($student);

    $this->actingAs($manager)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => '26XYZ0002HP',
            'reason' => 'Registry issued a corrected student number.',
        ])
        ->assertRedirect();

    expect($linked->fresh()->student_number)->toBe('26XYZ0002HP');
});

it('requires a reason when changing the student number', function (): void {
    $application = createVerifiedStudentApplication('NUM-F-'.strtoupper(Str::random(4)));
    $student = $application->student;
    $manager = createStudentNumberManager($student);

    $this->actingAs($manager)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => '26FFF0003HP',
            'reason' => 'too short',
        ])
        ->assertSessionHasErrors('reason');
});

it('forbids changing the student number without the permission', function (): void {
    $application = createVerifiedStudentApplication('NUM-G-'.strtoupper(Str::random(4)));
    $student = $application->student;

    $viewer = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $viewer->givePermissionTo(['view:students', 'viewAny:students', 'update:students']);

    $this->actingAs($viewer)
        ->patch(route('students.student-number.update', $student), [
            'student_number' => '26GGG0004HP',
            'reason' => 'No permission to change this student number.',
        ])
        ->assertForbidden();
});

it('rejects current intake applications and marks their class lists as failed', function (): void {
    $application = createVerifiedStudentApplication('STA-A-'.strtoupper(Str::random(4)));
    $student = $application->student;
    activateIntakePeriodForStatusTest($application);
    seedWorkflowStepsForStatusTest();

    $pastIntake = IntakePeriod::query()->create([
        'tenant_id' => $student->tenant_id,
        'name' => 'Past intake '.Str::random(5),
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => now()->subYear()->addMonth()->toDateString(),
        'is_active' => false,
        'is_continuous' => false,
        'status' => 'closed',
    ]);

    $pastApplication = StudentApplication::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'intake_period_id' => $pastIntake->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'workflow_step_id' => $application->workflow_step_id,
    ]);

    $manager = createStudentStatusManager($student);

    $this->actingAs($manager)
        ->patch(route('students.status.update', $student), [
            'status' => WorkflowStepEnum::REJECTED->slug(),
            'reason' => 'Applicant failed to meet the entry requirements.',
        ])
        ->assertRedirect();

    $rejectedStep = resolveWorkflowStep(WorkflowStepEnum::REJECTED);

    expect($application->fresh()->workflow_step_id)->toBe($rejectedStep->id)
        ->and($pastApplication->fresh()->workflow_step_id)->not->toBe($rejectedStep->id)
        ->and(ClassList::query()->where('student_application_id', $application->id)->value('type'))
        ->toBe(ClassListTypeEnum::FAILED);

    $activity = Activity::query()
        ->where('subject_type', $student->getMorphClass())
        ->where('subject_id', $student->id)
        ->where('event', 'status-changed')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['new_status'])->toBe(WorkflowStepEnum::REJECTED->name())
        ->and($activity->properties['reason'])->toBe('Applicant failed to meet the entry requirements.')
        ->and($activity->causer_id)->toBe($manager->id);
});

it('refuses to mark a student enrolled when a current intake application has no level', function (): void {
    $application = createVerifiedStudentApplication('STA-B-'.strtoupper(Str::random(4)));
    $student = $application->student;
    activateIntakePeriodForStatusTest($application);

    seedWorkflowStepsForStatusTest();

    $originalStepId = $application->workflow_step_id;

    removeApplicationLevelForStatusTest($application);

    $manager = createStudentStatusManager($student);

    $this->actingAs($manager)
        ->patch(route('students.status.update', $student), [
            'status' => WorkflowStepEnum::ENROLLED->slug(),
            'reason' => 'Trying to enrol a student without a level.',
        ])
        ->assertSessionHasErrors('status');

    expect($application->fresh()->workflow_step_id)->toBe($originalStepId);
});

it('fails when the student has no applications in the current intake', function (): void {
    $application = createVerifiedStudentApplication('STA-C-'.strtoupper(Str::random(4)));
    $student = $application->student;
    seedWorkflowStepsForStatusTest();

    $application->intakePeriod->update([
        'is_active' => false,
        'status' => 'closed',
    ]);

    $manager = createStudentStatusManager($student);

    $this->actingAs($manager)
        ->patch(route('students.status.update', $student), [
            'status' => WorkflowStepEnum::REJECTED->slug(),
            'reason' => 'No current intake application exists for this student.',
        ])
        ->assertSessionHasErrors('status');
});

it('rejects an unknown student status', function (): void {
    $application = createVerifiedStudentApplication('STA-D-'.strtoupper(Str::random(4)));
    $student = $application->student;
    activateIntakePeriodForStatusTest($application);

    $manager = createStudentStatusManager($student);

    $this->actingAs($manager)
        ->patch(route('students.status.update', $student), [
            'status' => 'not-a-status',
            'reason' => 'Trying to apply a status that does not exist.',
        ])
        ->assertSessionHasErrors('status');
});

it('forbids changing the student status without the permission', function (): void {
    $application = createVerifiedStudentApplication('STA-E-'.strtoupper(Str::random(4)));
    $student = $application->student;
    activateIntakePeriodForStatusTest($application);
    seedWorkflowStepsForStatusTest();

    $viewer = User::factory()->create(['tenant_id' => $student->tenant_id]);
    $viewer->givePermissionTo(['view:students', 'viewAny:students', 'update:students']);

    $this->actingAs($viewer)
        ->patch(route('students.status.update', $student), [
            'status' => WorkflowStepEnum::REJECTED->slug(),
            'reason' => 'No permission to change this student status.',
        ])
        ->assertForbidden();
});
