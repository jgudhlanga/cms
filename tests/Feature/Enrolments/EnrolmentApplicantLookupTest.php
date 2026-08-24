<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Rbac\Permission;
use App\Models\Users\User;

function lookupActingAsClassListStaff(?int $tenantId = null, array $permissions = ['verify:class-lists', 'confirm:class-lists']): User
{
    $user = User::factory()->create(array_filter([
        'tenant_id' => $tenantId,
    ]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

test('applicant lookup requires typed browse permission', function () {
    $application = createVerifiedStudentApplication('LOOKUP-AUTH-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $application->intake_period_id,
            'q' => 'LOOKUP',
        ]))
        ->assertForbidden();
});

test('applicant lookup requires a search query or a course', function () {
    $application = createVerifiedStudentApplication('LOOKUP-QUERY-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $application->tenant_id);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $application->intake_period_id,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q', 'department_course_id']);
});

test('applicant lookup returns top course suggestions without a search query', function () {
    $seed = createVerifiedStudentApplication('LOOKUP-SUGGEST-00');
    ClassList::query()->where('student_application_id', $seed->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);
    $seed->student->user->update([
        'first_name' => 'Test',
        'last_name' => 'Able',
    ]);

    $lastNames = ['Baker', 'Chen', 'Diaz', 'Ellis', 'Frost'];
    $extraIds = [];

    foreach ($lastNames as $index => $lastName) {
        $application = createVerifiedStudentApplication('LOOKUP-SUGGEST-0'.($index + 1));
        $application->update([
            'intake_period_id' => $seed->intake_period_id,
            'institution_department_id' => $seed->institution_department_id,
            'department_level_id' => $seed->department_level_id,
            'department_course_id' => $seed->department_course_id,
        ]);
        ClassList::query()->where('student_application_id', $application->id)->update([
            'type' => ClassListTypeEnum::PROVISIONAL->value,
        ]);
        $application->student->user->update([
            'first_name' => 'Test',
            'last_name' => $lastName,
        ]);
        $extraIds[] = (int) $application->id;
    }

    $otherCourseApplication = createVerifiedStudentApplication('LOOKUP-SUGGEST-OTHER');
    $otherCourseApplication->update([
        'intake_period_id' => $seed->intake_period_id,
    ]);
    ClassList::query()->where('student_application_id', $otherCourseApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $seed->tenant_id);

    $response = $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $seed->intake_period_id,
            'institution_department_id' => $seed->institution_department_id,
            'department_level_id' => $seed->department_level_id,
            'department_course_id' => $seed->department_course_id,
        ]))
        ->assertOk()
        ->assertJsonCount(5);

    $applicationIds = collect($response->json())->pluck('applicationId')->map(fn ($id) => (int) $id);

    expect($applicationIds)->toContain((int) $seed->id)
        ->and($applicationIds)->toContain($extraIds[0], $extraIds[1], $extraIds[2], $extraIds[3])
        ->and($applicationIds)->not->toContain($extraIds[4])
        ->and($applicationIds)->not->toContain((int) $otherCourseApplication->id);
});

test('applicant lookup returns applications scoped to type and intake', function () {
    $application = createVerifiedStudentApplication('LOOKUP-SCOPE-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $otherIntakeApplication = createVerifiedStudentApplication('LOOKUP-SCOPE-02');
    ClassList::query()->where('student_application_id', $otherIntakeApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $application->tenant_id);

    $response = $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $application->intake_period_id,
            'institution_department_id' => $application->institution_department_id,
            'q' => $application->application_tracking_number,
        ]))
        ->assertOk();

    $applicationIds = collect($response->json())->pluck('applicationId')->map(fn ($id) => (int) $id);

    expect($applicationIds)->toContain((int) $application->id)
        ->and($applicationIds)->not->toContain((int) $otherIntakeApplication->id);

    $response->assertJsonPath('0.classListType', ClassListTypeEnum::PROVISIONAL->value);
});

test('applicant lookup finds applicants across departments without department filter', function () {
    $primaryApplication = createVerifiedStudentApplication('LOOKUP-CROSS-01');
    $otherDepartmentApplication = createVerifiedStudentApplication('LOOKUP-CROSS-02');

    $otherDepartmentApplication->update([
        'intake_period_id' => $primaryApplication->intake_period_id,
    ]);

    ClassList::query()->where('student_application_id', $primaryApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);
    ClassList::query()->where('student_application_id', $otherDepartmentApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $primaryApplication->tenant_id);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $primaryApplication->intake_period_id,
            'q' => $otherDepartmentApplication->application_tracking_number,
        ]))
        ->assertOk()
        ->assertJsonPath('0.applicationId', $otherDepartmentApplication->id);
});

test('applicant lookup department filter excludes other departments', function () {
    $primaryApplication = createVerifiedStudentApplication('LOOKUP-FILTER-01');
    $otherDepartmentApplication = createVerifiedStudentApplication('LOOKUP-FILTER-02');

    $otherDepartmentApplication->update([
        'intake_period_id' => $primaryApplication->intake_period_id,
    ]);

    ClassList::query()->where('student_application_id', $primaryApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);
    ClassList::query()->where('student_application_id', $otherDepartmentApplication->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $primaryApplication->tenant_id);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $primaryApplication->intake_period_id,
            'institution_department_id' => $primaryApplication->institution_department_id,
            'q' => $otherDepartmentApplication->application_tracking_number,
        ]))
        ->assertOk()
        ->assertJsonCount(0);
});

test('applicant lookup matches tracking number and excludes verified type', function () {
    $application = createVerifiedStudentApplication('LOOKUP-TRN-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $application->tenant_id);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'intake_period_id' => $application->intake_period_id,
            'q' => $application->application_tracking_number,
        ]))
        ->assertOk()
        ->assertJsonPath('0.applicationId', $application->id);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'type' => ClassListTypeEnum::VERIFIED->value,
            'intake_period_id' => $application->intake_period_id,
            'q' => $application->application_tracking_number,
        ]))
        ->assertOk()
        ->assertJsonCount(0);
});

test('untyped applicant lookup requires at least one class list browse permission', function () {
    $application = createVerifiedStudentApplication('LOOKUP-UNTYPED-AUTH-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'intake_period_id' => $application->intake_period_id,
            'q' => 'LOOKUP',
        ]))
        ->assertForbidden();
});

test('untyped applicant lookup requires a search query or a course', function () {
    $application = createVerifiedStudentApplication('LOOKUP-UNTYPED-QUERY-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = lookupActingAsClassListStaff((int) $application->tenant_id);

    $this->actingAs($user)
        ->getJson(route('enrolments.applicant-lookup', [
            'intake_period_id' => $application->intake_period_id,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q', 'department_course_id']);
});

test('untyped applicant lookup is scoped to statuses the user can open', function () {
    $provisional = createVerifiedStudentApplication('LOOKUP-UNTYPED-P');
    $verified = createVerifiedStudentApplication('LOOKUP-UNTYPED-V');
    $final = createVerifiedStudentApplication('LOOKUP-UNTYPED-F');

    $verified->update(['intake_period_id' => $provisional->intake_period_id]);
    $final->update(['intake_period_id' => $provisional->intake_period_id]);

    ClassList::query()->where('student_application_id', $provisional->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);
    ClassList::query()->where('student_application_id', $verified->id)->update([
        'type' => ClassListTypeEnum::VERIFIED->value,
    ]);
    ClassList::query()->where('student_application_id', $final->id)->update([
        'type' => ClassListTypeEnum::FINAL->value,
    ]);

    foreach ([$provisional, $verified, $final] as $application) {
        $application->student->user->update([
            'first_name' => 'ZimbaLookup',
            'last_name' => 'CrossType',
        ]);
    }

    $tenantId = (int) $provisional->tenant_id;
    $query = [
        'intake_period_id' => $provisional->intake_period_id,
        'q' => 'ZimbaLookup',
    ];

    $verifyIds = collect($this->actingAs(lookupActingAsClassListStaff($tenantId, ['verify:class-lists']))
        ->getJson(route('enrolments.applicant-lookup', $query))
        ->assertOk()
        ->json())->pluck('applicationId')->map(fn ($id) => (int) $id);

    expect($verifyIds)->toContain((int) $provisional->id)
        ->and($verifyIds)->not->toContain((int) $verified->id)
        ->and($verifyIds)->not->toContain((int) $final->id);

    $confirmIds = collect($this->actingAs(lookupActingAsClassListStaff($tenantId, ['confirm:class-lists']))
        ->getJson(route('enrolments.applicant-lookup', $query))
        ->assertOk()
        ->json())->pluck('applicationId')->map(fn ($id) => (int) $id);

    expect($confirmIds)->toContain((int) $verified->id)
        ->and($confirmIds)->not->toContain((int) $provisional->id)
        ->and($confirmIds)->not->toContain((int) $final->id);

    $finalIds = collect($this->actingAs(lookupActingAsClassListStaff($tenantId, ['manage-final:class-lists']))
        ->getJson(route('enrolments.applicant-lookup', $query))
        ->assertOk()
        ->json())->pluck('applicationId')->map(fn ($id) => (int) $id);

    expect($finalIds)->toContain((int) $final->id)
        ->and($finalIds)->not->toContain((int) $provisional->id)
        ->and($finalIds)->not->toContain((int) $verified->id);

    $allTypes = collect($this->actingAs(lookupActingAsClassListStaff($tenantId, [
        'verify:class-lists',
        'confirm:class-lists',
        'manage-final:class-lists',
    ]))->getJson(route('enrolments.applicant-lookup', $query))
        ->assertOk()
        ->json());

    expect($allTypes->pluck('applicationId')->map(fn ($id) => (int) $id)->all())
        ->toContain((int) $provisional->id, (int) $verified->id, (int) $final->id)
        ->and($allTypes->pluck('classListType')->all())
        ->toContain(
            ClassListTypeEnum::PROVISIONAL->value,
            ClassListTypeEnum::VERIFIED->value,
            ClassListTypeEnum::FINAL->value,
        );
});

test('verify page exposes invalid id flag', function () {
    $application = createVerifiedStudentApplication('VERIFY-PROPS-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $application->student->update([
        'id_number' => 'invalid-id',
        'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
    ]);
    $user = lookupActingAsClassListStaff((int) $application->tenant_id);

    $this->actingAs($user)
        ->get(route('enrolments.verify', [
            'student_application' => $application->id,
            'type' => ClassListTypeEnum::PROVISIONAL->value,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrolments/ApplicationVerification')
            ->where('application.attributes.idNumberValid', false));
});
