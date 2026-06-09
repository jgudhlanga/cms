<?php

use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Sponsor;
use App\Models\Students\Student;
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
        ->assertJsonPath('conflict.idNumber', '63-1234567N63');
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
