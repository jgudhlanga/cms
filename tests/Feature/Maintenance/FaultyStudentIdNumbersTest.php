<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Sponsor;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Support\Str;

require_once __DIR__.'/MaintenanceControllerTest.php';

function createFaultyStudentTestRecord(User $rootUser, string $idNumber, ?string $studentNumber = null): Student
{
    $studentUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Faulty',
        'last_name' => 'Student',
        'email' => 'faulty.'.Str::lower(Str::random(8)).'@example.test',
    ]);

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID']);

    return Student::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => $idNumber,
        'student_number' => $studentNumber ?? 'FAULTY-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);
}

function responseStudentIds($response): array
{
    return collect($response->json('data'))
        ->pluck('id')
        ->map(static fn ($id) => (int) $id)
        ->all();
}

function createMergePreviewStudentApplication(
    Student $student,
    WorkflowStepEnum $workflowStep = WorkflowStepEnum::REVIEW,
): StudentApplication {
    $template = createVerifiedStudentApplication('TMP-'.strtoupper(Str::random(4)));
    $departmentStep = resolveDepartmentApplicationStep($template, $workflowStep);

    $template->update([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'department_application_step_id' => $departmentStep->id,
    ]);

    $template->institutionDepartment()->update(['tenant_id' => $student->tenant_id]);
    $template->departmentLevel()->update(['tenant_id' => $student->tenant_id]);
    $template->departmentCourse()->update(['tenant_id' => $student->tenant_id]);
    ClassList::query()
        ->where('student_application_id', $template->id)
        ->update(['tenant_id' => $student->tenant_id]);

    return $template->fresh([
        'institutionDepartment.department',
        'departmentLevel.level',
        'departmentCourse.course',
        'intakePeriod',
        'modeOfStudy',
        'departmentWorkflowStep.workflowStep',
        'classList',
    ]);
}

it('redirects guests from faulty student ids page', function (): void {
    $this->get(route('maintenance.faulty-student-ids'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from faulty student ids page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.faulty-student-ids'))
        ->assertForbidden();
});

it('renders faulty student ids page for root users', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.faulty-student-ids'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('maintenance/FaultyStudentIds'));
});

it('returns unauthorized for guests on faulty student ids data endpoint', function (): void {
    $this->getJson(route('maintenance.faulty-student-ids.data'))
        ->assertUnauthorized();
});

it('forbids users without root manage from faulty student ids data endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->getJson(route('maintenance.faulty-student-ids.data'))
        ->assertForbidden();
});

it('lists students with invalid id number format only', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $invalidStudent = createFaultyStudentTestRecord($rootUser, 'invalid-id');
    $validStudent = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'VALID-'.strtoupper(Str::random(4)));

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID']);
    $missingIdUser = User::factory()->create(['tenant_id' => $rootUser->tenant_id]);
    $missingIdStudent = Student::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => $missingIdUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => null,
        'student_number' => 'NULL-ID-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);

    $response = $this->getJson(route('maintenance.faulty-student-ids.data'));
    $response->assertOk();

    $ids = responseStudentIds($response);
    expect($ids)->toContain((int) $invalidStudent->id)
        ->and($ids)->not->toContain((int) $validStudent->id)
        ->and($ids)->not->toContain((int) $missingIdStudent->id);
});

it('filters faulty student ids by search term', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $student = createFaultyStudentTestRecord($rootUser, 'bad-format', 'SEARCH-ME-123');

    $response = $this->getJson(route('maintenance.faulty-student-ids.data', ['search' => 'SEARCH-ME-123']));
    $response->assertOk();

    expect(responseStudentIds($response))->toContain((int) $student->id);
});

it('includes suggested id number when normalization fixes format', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $student = createFaultyStudentTestRecord($rootUser, '631234567N63');

    $response = $this->getJson(route('maintenance.faulty-student-ids.data'));
    $response->assertOk();

    $matched = collect($response->json('data'))->firstWhere('id', $student->id);
    expect($matched['attributes']['suggestedIdNumber'])->toBe('63-1234567N63');
});

it('updates invalid student id number via maintenance fix endpoint', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $student = createFaultyStudentTestRecord($rootUser, '631234567N63');

    $this->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
        'id_number' => '63-1234567N63',
    ])->assertOk();

    expect($student->fresh()->id_number)->toBe('63-1234567N63');

    $this->getJson(route('maintenance.faulty-student-ids.data'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects invalid format on maintenance fix endpoint', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $student = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $this->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
        'id_number' => 'still-invalid',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['id_number']);
});

it('returns conflict when corrected id is already taken', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'TAKEN-'.strtoupper(Str::random(4)));
    $student = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $this->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
        'id_number' => '63-1234567N63',
    ])->assertStatus(409)
        ->assertJsonPath('conflict.conflictingStudentId', $target->id)
        ->assertJsonPath('conflict.idNumber', '63-1234567N63')
        ->assertJsonPath(
            'conflict.mergeUrl',
            route('maintenance.faulty-student-ids.merge', [
                'student' => $student->id,
                'target' => $target->id,
                'id_number' => '63-1234567N63',
            ]),
        );
});

it('loads merge preview using merge url from conflict payload', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-URL-'.strtoupper(Str::random(4)));
    $student = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $mergeUrl = $this->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
        'id_number' => '63-1234567N63',
    ])->assertStatus(409)
        ->json('conflict.mergeUrl');

    $this->get($mergeUrl)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/FaultyStudentIdMerge')
            ->where('preview.proposedIdNumber', '63-1234567N63'));
});

it('includes duplicate conflict metadata and phone number in list data', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'TAKEN-'.strtoupper(Str::random(4)));
    $target->user?->update(['phone_number' => '+263771234567']);

    $faulty = createFaultyStudentTestRecord($rootUser, '631234567N63', 'FAULTY-'.strtoupper(Str::random(4)));
    $faulty->user?->update(['phone_number' => '+263779999999']);

    $response = $this->getJson(route('maintenance.faulty-student-ids.data'));
    $response->assertOk();

    $matched = collect($response->json('data'))->firstWhere('id', $faulty->id);

    expect($matched['attributes']['phoneNumber'])->toBe('+263779999999')
        ->and($matched['attributes']['proposedIdNumber'])->toBe('63-1234567N63')
        ->and($matched['attributes']['rectificationStatus'])->toBe('duplicate_merge')
        ->and($matched['attributes']['conflict']['conflictingStudentId'])->toBe($target->id)
        ->and($matched['attributes']['conflict']['conflictingPhoneNumber'])->toBe('+263771234567')
        ->and($matched['attributes']['conflict']['mergePreviewUrl'])->toContain('/merge');
});

it('orders duplicate merge rows before ready to fix rows', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'TAKEN-'.strtoupper(Str::random(4)));
    $duplicateFaulty = createFaultyStudentTestRecord($rootUser, '631234567N63', 'DUP-'.strtoupper(Str::random(4)));
    $readyFaulty = createFaultyStudentTestRecord($rootUser, '639999999N63', 'READY-'.strtoupper(Str::random(4)));

    $ids = responseStudentIds($this->getJson(route('maintenance.faulty-student-ids.data')));

    $duplicateIndex = array_search((int) $duplicateFaulty->id, $ids, true);
    $readyIndex = array_search((int) $readyFaulty->id, $ids, true);

    expect($duplicateIndex)->not->toBeFalse()
        ->and($readyIndex)->not->toBeFalse()
        ->and($duplicateIndex)->toBeLessThan($readyIndex);
});

it('respects page_size query parameter on faulty student ids data endpoint', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $prefix = 'PAGE-SIZE-'.strtoupper(Str::random(4));

    foreach (range(1, 30) as $index) {
        createFaultyStudentTestRecord($rootUser, "invalid-{$index}", "{$prefix}-{$index}");
    }

    $this->getJson(route('maintenance.faulty-student-ids.data', [
        'search' => $prefix,
        'page_size' => 25,
    ]))
        ->assertOk()
        ->assertJsonCount(25, 'data')
        ->assertJsonPath('meta.per_page', 25);
});

it('returns a different page when page query parameter changes', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $prefix = 'PAGE-NAV-'.strtoupper(Str::random(4));

    foreach (range(1, 5) as $index) {
        createFaultyStudentTestRecord($rootUser, "bad-{$index}", "{$prefix}-{$index}");
    }

    $pageOneIds = responseStudentIds($this->getJson(route('maintenance.faulty-student-ids.data', [
        'search' => $prefix,
        'page_size' => 2,
        'page' => 1,
    ])));

    $pageTwoIds = responseStudentIds($this->getJson(route('maintenance.faulty-student-ids.data', [
        'search' => $prefix,
        'page_size' => 2,
        'page' => 2,
    ])));

    expect($pageOneIds)->toHaveCount(2)
        ->and($pageTwoIds)->toHaveCount(2)
        ->and($pageOneIds)->not->toEqual($pageTwoIds);
});

it('preserves page_size in pagination links', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $prefix = 'PAGE-LINK-'.strtoupper(Str::random(4));

    foreach (range(1, 5) as $index) {
        createFaultyStudentTestRecord($rootUser, "bad-link-{$index}", "{$prefix}-{$index}");
    }

    $response = $this->getJson(route('maintenance.faulty-student-ids.data', [
        'search' => $prefix,
        'page_size' => 2,
        'page' => 1,
    ]));

    $response->assertOk();

    expect($response->json('links.next'))->toContain('page_size=2');
});

it('redirects to faulty list when merge preview params are invalid', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $this->get(route('maintenance.faulty-student-ids.merge', ['student' => $faulty->id]))
        ->assertRedirect(route('maintenance.faulty-student-ids'))
        ->assertSessionHas('error');
});

it('returns conflict when corrected id is taken by a soft deleted student', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-2427958S47', 'TRASHED-'.strtoupper(Str::random(4)));
    $target->delete();
    $student = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $this->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
        'id_number' => '63-2427958S47',
    ])->assertStatus(409)
        ->assertJsonPath('conflict.conflictingStudentId', $target->id)
        ->assertJsonPath('conflict.idNumber', '63-2427958S47');
});

it('rejects fix when student id number is already valid', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $student = createFaultyStudentTestRecord($rootUser, '63-1234567N63');

    $this->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
        'id_number' => '63-9999999H63',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['id_number']);
});

it('forbids users without root manage from maintenance fix endpoint', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $student = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $user = User::factory()->create(['tenant_id' => $rootUser->tenant_id]);

    $this->actingAs($user)
        ->patchJson(route('maintenance.faulty-student-ids.fix', $student), [
            'id_number' => '63-1234567N63',
        ])
        ->assertForbidden();
});

it('renders merge preview page for root users', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-TGT-'.strtoupper(Str::random(4)));
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $this->get(route('maintenance.faulty-student-ids.merge', [
        'student' => $faulty->id,
        'target' => $target->id,
        'id_number' => '63-1234567N63',
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/FaultyStudentIdMerge')
            ->has('preview.proposedIdNumber')
            ->where('preview.proposedIdNumber', '63-1234567N63'));
});

it('flashes merge result and passes survivor profile to faulty student ids page', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-RESULT-'.strtoupper(Str::random(4)));
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id', 'MERGE-FAULTY-'.strtoupper(Str::random(4)));

    $this->post(route('maintenance.faulty-student-ids.merge.execute'), [
        'source_student_id' => $faulty->id,
        'target_student_id' => $target->id,
        'survivor_student_id' => $target->id,
        'id_number' => '63-1234567N63',
    ])
        ->assertRedirect(route('maintenance.faulty-student-ids'))
        ->assertSessionHas('mergeResult.studentId', $target->id)
        ->assertSessionHas('mergeResult.userId', $target->user_id)
        ->assertSessionHas('mergeResult.idNumber', '63-1234567N63');

    $this->get(route('maintenance.faulty-student-ids'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/FaultyStudentIds')
            ->has('mergeResult')
            ->where('mergeResult.studentId', $target->id)
            ->where('mergeResult.userId', $target->user_id)
            ->where('mergeResult.idNumber', '63-1234567N63')
            ->where('mergeResult.studentNumber', $target->student_number));
});

it('merges accounts keeping the existing id owner as survivor', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-KEEP-'.strtoupper(Str::random(4)));
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id', 'MERGE-FAULTY-'.strtoupper(Str::random(4)));

    Sponsor::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'name' => 'Faulty Sponsor',
        'student_id' => $faulty->id,
    ]);

    $this->post(route('maintenance.faulty-student-ids.merge.execute'), [
        'source_student_id' => $faulty->id,
        'target_student_id' => $target->id,
        'survivor_student_id' => $target->id,
        'id_number' => '63-1234567N63',
    ])->assertRedirect(route('maintenance.faulty-student-ids'));

    expect(Student::query()->find($faulty->id))->toBeNull()
        ->and(Student::query()->find($target->id))->not->toBeNull()
        ->and(Sponsor::query()->where('student_id', $target->id)->count())->toBe(1)
        ->and(User::query()->find($faulty->user_id))->toBeNull();

    $this->getJson(route('maintenance.faulty-student-ids.data'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('merges accounts keeping the faulty account as survivor', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-ABS-'.strtoupper(Str::random(4)));
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id', 'MERGE-SURV-'.strtoupper(Str::random(4)));

    Sponsor::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'name' => 'Target Sponsor',
        'student_id' => $target->id,
    ]);

    $this->post(route('maintenance.faulty-student-ids.merge.execute'), [
        'source_student_id' => $faulty->id,
        'target_student_id' => $target->id,
        'survivor_student_id' => $faulty->id,
        'id_number' => '63-1234567N63',
    ])->assertRedirect(route('maintenance.faulty-student-ids'));

    $survivor = Student::query()->find($faulty->id);

    expect($survivor)->not->toBeNull()
        ->and($survivor?->id_number)->toBe('63-1234567N63')
        ->and(Student::query()->find($target->id))->toBeNull()
        ->and(Sponsor::query()->where('student_id', $faulty->id)->count())->toBe(1)
        ->and(User::query()->find($target->user_id))->toBeNull();
});

it('forbids users without root manage from merge execute endpoint', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63');
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id');

    $user = User::factory()->create(['tenant_id' => $rootUser->tenant_id]);

    $this->actingAs($user)
        ->post(route('maintenance.faulty-student-ids.merge.execute'), [
            'source_student_id' => $faulty->id,
            'target_student_id' => $target->id,
            'survivor_student_id' => $target->id,
            'id_number' => '63-1234567N63',
        ])
        ->assertForbidden();
});

it('includes application statuses on merge preview page', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-APP-TGT-'.strtoupper(Str::random(4)));
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id', 'MERGE-APP-FLT-'.strtoupper(Str::random(4)));

    $faultyProgram = createMergePreviewStudentApplication($faulty, WorkflowStepEnum::REVIEW);
    $targetProgram = createMergePreviewStudentApplication($target, WorkflowStepEnum::ACCEPTED);

    $this->get(route('maintenance.faulty-student-ids.merge', [
        'student' => $faulty->id,
        'target' => $target->id,
        'id_number' => '63-1234567N63',
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/FaultyStudentIdMerge')
            ->has('preview.source.applications', 1)
            ->has('preview.target.applications', 1)
            ->where('preview.source.applications.0.id', $faultyProgram->id)
            ->where('preview.source.applications.0.applicationStatus', WorkflowStepEnum::REVIEW->name())
            ->where('preview.source.applications.0.canReject', true)
            ->where('preview.target.applications.0.id', $targetProgram->id)
            ->where('preview.target.applications.0.applicationStatus', WorkflowStepEnum::ACCEPTED->name())
            ->where('preview.target.applications.0.canReject', true));
});

it('rejects an application from the merge preview page', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63', 'MERGE-REJ-TGT-'.strtoupper(Str::random(4)));
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id', 'MERGE-REJ-FLT-'.strtoupper(Str::random(4)));
    $program = createMergePreviewStudentApplication($faulty, WorkflowStepEnum::REVIEW);

    $this->from(route('maintenance.faulty-student-ids.merge', [
        'student' => $faulty->id,
        'target' => $target->id,
        'id_number' => '63-1234567N63',
    ]))->patch(route('maintenance.faulty-student-ids.merge.reject-application', $program), [
        'source_student_id' => $faulty->id,
        'target_student_id' => $target->id,
    ])->assertRedirect()
        ->assertSessionHas('success');

    expect($program->fresh()->departmentWorkflowStep?->workflowStep?->slug)
        ->toBe(WorkflowStepEnum::REJECTED->slug())
        ->and(ClassList::query()->where('student_application_id', $program->id)->first()?->type)
        ->toBe(ClassListTypeEnum::FAILED);
});

it('forbids rejecting terminal applications from merge preview', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63');
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id');
    $program = createMergePreviewStudentApplication($faulty, WorkflowStepEnum::ENROLLED);

    $this->patch(route('maintenance.faulty-student-ids.merge.reject-application', $program), [
        'source_student_id' => $faulty->id,
        'target_student_id' => $target->id,
    ])->assertSessionHasErrors('student_application');
});

it('forbids rejecting applications that are not part of the merge preview', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63');
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id');
    $otherStudent = createFaultyStudentTestRecord($rootUser, 'also-invalid', 'OTHER-'.strtoupper(Str::random(4)));
    $program = createMergePreviewStudentApplication($otherStudent, WorkflowStepEnum::REVIEW);

    $this->patch(route('maintenance.faulty-student-ids.merge.reject-application', $program), [
        'source_student_id' => $faulty->id,
        'target_student_id' => $target->id,
    ])->assertSessionHasErrors('student_application');
});

it('forbids users without root manage from merge reject endpoint', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = createFaultyStudentTestRecord($rootUser, '63-1234567N63');
    $faulty = createFaultyStudentTestRecord($rootUser, 'invalid-id');
    $program = createMergePreviewStudentApplication($faulty, WorkflowStepEnum::REVIEW);
    $user = User::factory()->create(['tenant_id' => $rootUser->tenant_id]);

    $this->actingAs($user)
        ->patch(route('maintenance.faulty-student-ids.merge.reject-application', $program), [
            'source_student_id' => $faulty->id,
            'target_student_id' => $target->id,
        ])
        ->assertForbidden();
});
