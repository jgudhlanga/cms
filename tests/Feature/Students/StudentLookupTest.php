<?php

use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\Institution\Staff;
use App\Models\Rbac\Permission;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;

function studentLookupStaff(int $tenantId, array $permissions = ['viewAny:students']): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

/**
 * Build an applicant that is actually enrolled — the lookup only ever returns students
 * with a `student_enrolments` row.
 */
function enrolledLookupStudent(string $studentNumber, array $userAttributes = []): StudentApplication
{
    $application = createVerifiedStudentApplication($studentNumber);

    if ($userAttributes !== []) {
        $application->student->user->update($userAttributes);
    }

    attachHostelApplicationEnrolment($application);

    return $application->refresh();
}

test('student lookup requires student view permission', function () {
    $application = enrolledLookupStudent('LOOKUP-STU-AUTH-01');

    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);

    $this->actingAs($user)
        ->getJson(route('students.lookup', ['name' => 'Test']))
        ->assertForbidden();
});

test('student lookup requires a name, student details, or a course', function () {
    $application = enrolledLookupStudent('LOOKUP-STU-QUERY-01');

    $user = studentLookupStaff((int) $application->tenant_id);

    $this->actingAs($user)
        ->getJson(route('students.lookup', [
            'institution_department_id' => $application->institution_department_id,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'search', 'department_course_id']);
});

test('student lookup returns top course suggestions without a search term', function () {
    $seed = enrolledLookupStudent('LOOKUP-STU-SUGGEST-00', ['first_name' => 'Test', 'last_name' => 'Able']);

    $lastNames = ['Baker', 'Chen', 'Diaz', 'Ellis', 'Frost'];
    $extraIds = [];

    foreach ($lastNames as $index => $lastName) {
        $application = createVerifiedStudentApplication('LOOKUP-STU-SUGGEST-0'.($index + 1));
        $application->update([
            'institution_department_id' => $seed->institution_department_id,
            'department_level_id' => $seed->department_level_id,
            'department_course_id' => $seed->department_course_id,
        ]);
        $application->student->user->update(['first_name' => 'Test', 'last_name' => $lastName]);
        attachHostelApplicationEnrolment($application->refresh());

        $extraIds[] = (int) $application->student_id;
    }

    $otherCourse = enrolledLookupStudent('LOOKUP-STU-SUGGEST-OTHER');

    $user = studentLookupStaff((int) $seed->tenant_id);

    $response = $this->actingAs($user)
        ->getJson(route('students.lookup', [
            'institution_department_id' => $seed->institution_department_id,
            'department_level_id' => $seed->department_level_id,
            'department_course_id' => $seed->department_course_id,
        ]))
        ->assertOk()
        ->assertJsonCount(5);

    $studentIds = collect($response->json())->pluck('studentId')->map(fn ($id) => (int) $id);

    // Ordered by last name, so Able + Baker..Ellis are returned and Frost falls off the limit.
    expect($studentIds)->toContain((int) $seed->student_id)
        ->and($studentIds)->toContain($extraIds[0], $extraIds[1], $extraIds[2], $extraIds[3])
        ->and($studentIds)->not->toContain($extraIds[4])
        ->and($studentIds)->not->toContain((int) $otherCourse->student_id);
});

test('student lookup matches on name across first, middle, and last name', function () {
    $match = enrolledLookupStudent('LOOKUP-STU-NAME-01', [
        'first_name' => 'Tafadzwa',
        'middle_name' => 'Kudakwashe',
        'last_name' => 'Mutasa',
    ]);

    $other = enrolledLookupStudent('LOOKUP-STU-NAME-02', [
        'first_name' => 'Rumbidzai',
        'middle_name' => null,
        'last_name' => 'Chikwanha',
    ]);

    $user = studentLookupStaff((int) $match->tenant_id);

    foreach (['Tafadzwa', 'Kudakwashe', 'Mutasa'] as $term) {
        $studentIds = collect(
            $this->actingAs($user)
                ->getJson(route('students.lookup', ['name' => $term]))
                ->assertOk()
                ->json()
        )->pluck('studentId')->map(fn ($id) => (int) $id);

        expect($studentIds)->toContain((int) $match->student_id)
            ->and($studentIds)->not->toContain((int) $other->student_id);
    }
});

test('student lookup matches student number and id number with or without hyphens', function () {
    $application = enrolledLookupStudent('LOOKUP-STU-NUM-01');
    $application->student->update(['id_number' => '63-1234567A22']);

    $other = enrolledLookupStudent('LOOKUP-STU-NUM-02');
    $other->student->update(['id_number' => '75-9876543B11']);

    $user = studentLookupStaff((int) $application->tenant_id);

    foreach (['LOOKUP-STU-NUM-01', '63-1234567A22', '631234567A22'] as $term) {
        $studentIds = collect(
            $this->actingAs($user)
                ->getJson(route('students.lookup', ['search' => $term]))
                ->assertOk()
                ->json()
        )->pluck('studentId')->map(fn ($id) => (int) $id);

        expect($studentIds)->toContain((int) $application->student_id)
            ->and($studentIds)->not->toContain((int) $other->student_id);
    }
});

test('student lookup name and student details terms are combined', function () {
    $match = enrolledLookupStudent('LOOKUP-STU-BOTH-01', ['first_name' => 'Anesu', 'last_name' => 'Marange']);
    $sameName = enrolledLookupStudent('LOOKUP-STU-BOTH-02', ['first_name' => 'Anesu', 'last_name' => 'Marange']);

    $user = studentLookupStaff((int) $match->tenant_id);

    $studentIds = collect(
        $this->actingAs($user)
            ->getJson(route('students.lookup', [
                'name' => 'Anesu',
                'search' => 'LOOKUP-STU-BOTH-01',
            ]))
            ->assertOk()
            ->json()
    )->pluck('studentId')->map(fn ($id) => (int) $id);

    expect($studentIds)->toContain((int) $match->student_id)
        ->and($studentIds)->not->toContain((int) $sameName->student_id);
});

test('student lookup department filter excludes other departments', function () {
    $primary = enrolledLookupStudent('LOOKUP-STU-DEPT-01', ['first_name' => 'Zvikomborero', 'last_name' => 'Nyoni']);
    $other = enrolledLookupStudent('LOOKUP-STU-DEPT-02', ['first_name' => 'Zvikomborero', 'last_name' => 'Nyoni']);

    $user = studentLookupStaff((int) $primary->tenant_id);

    $studentIds = collect(
        $this->actingAs($user)
            ->getJson(route('students.lookup', [
                'institution_department_id' => $primary->institution_department_id,
                'name' => 'Zvikomborero',
            ]))
            ->assertOk()
            ->json()
    )->pluck('studentId')->map(fn ($id) => (int) $id);

    expect($studentIds)->toContain((int) $primary->student_id)
        ->and($studentIds)->not->toContain((int) $other->student_id);
});

test('student lookup returns a student with several enrolments only once', function () {
    $application = enrolledLookupStudent('LOOKUP-STU-DUP-01', ['first_name' => 'Farai', 'last_name' => 'Chidzero']);

    $secondApplication = createVerifiedStudentApplication('LOOKUP-STU-DUP-01-B');
    $secondApplication->update(['student_id' => $application->student_id]);
    attachHostelApplicationEnrolment($secondApplication->refresh());

    expect(StudentEnrolment::query()->where('student_id', $application->student_id)->count())->toBe(2);

    $user = studentLookupStaff((int) $application->tenant_id);

    $studentIds = collect(
        $this->actingAs($user)
            ->getJson(route('students.lookup', ['name' => 'Chidzero']))
            ->assertOk()
            ->json()
    )->pluck('studentId')->map(fn ($id) => (int) $id);

    expect($studentIds->filter(fn (int $id) => $id === (int) $application->student_id))->toHaveCount(1);
});

test('student lookup excludes applicants who are not enrolled', function () {
    $enrolled = enrolledLookupStudent('LOOKUP-STU-UNENROLLED-01', ['first_name' => 'Tinashe', 'last_name' => 'Gwena']);

    $applicantOnly = createVerifiedStudentApplication('LOOKUP-STU-UNENROLLED-02');
    $applicantOnly->student->user->update(['first_name' => 'Tinashe', 'last_name' => 'Gwena']);

    $user = studentLookupStaff((int) $enrolled->tenant_id);

    $studentIds = collect(
        $this->actingAs($user)
            ->getJson(route('students.lookup', ['name' => 'Gwena']))
            ->assertOk()
            ->json()
    )->pluck('studentId')->map(fn ($id) => (int) $id);

    expect($studentIds)->toContain((int) $enrolled->student_id)
        ->and($studentIds)->not->toContain((int) $applicantOnly->student_id);
});

test('department scoped user only sees students in their own department', function () {
    $own = enrolledLookupStudent('LOOKUP-STU-SCOPE-01', ['first_name' => 'Nyasha', 'last_name' => 'Mabhena']);
    $foreign = enrolledLookupStudent('LOOKUP-STU-SCOPE-02', ['first_name' => 'Nyasha', 'last_name' => 'Mabhena']);

    $user = studentLookupStaff((int) $own->tenant_id, ['viewAny:students', 'viewOnlyOwnDepartment:departments']);

    $staff = Staff::query()->create([
        'tenant_id' => $own->tenant_id,
        'user_id' => $user->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'employment_type_id' => EmploymentType::query()->firstOrCreate(
            ['name' => EmploymentTypeEnum::FULL_TIME->value],
            ['description' => EmploymentTypeEnum::FULL_TIME->description()],
        )->id,
        'employee_number' => 'EMP-'.strtoupper(str()->random(6)),
    ]);
    $staff->institutionDepartments()->attach($own->institution_department_id);

    // No department filter — scoping alone must exclude the other department.
    $studentIds = collect(
        $this->actingAs($user)
            ->getJson(route('students.lookup', ['name' => 'Mabhena']))
            ->assertOk()
            ->json()
    )->pluck('studentId')->map(fn ($id) => (int) $id);

    expect($studentIds)->toContain((int) $own->student_id)
        ->and($studentIds)->not->toContain((int) $foreign->student_id);

    // Explicitly asking for the other department must not widen the scope.
    $this->actingAs($user)
        ->getJson(route('students.lookup', [
            'institution_department_id' => $foreign->institution_department_id,
            'name' => 'Mabhena',
        ]))
        ->assertOk()
        ->assertJsonCount(0);
});
