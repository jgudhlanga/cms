<?php

declare(strict_types=1);

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\Examinations\ExaminationResult;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentExamResult;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\StudentExamResultAccessService;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

function createAdminExamResultsStudent(array $overrides = []): array
{
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
    $suffix = uniqid();

    $idType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->label()],
    );

    $student = Student::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
        'title_id' => Title::query()->create(['name' => 'Mr AER '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male AER '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single AER '.$suffix])->id,
        'id_type_id' => $idType->id,
        'passport_number' => 'P'.strtoupper(substr($suffix, -8)),
        'student_number' => 'H'.strtoupper(substr($suffix, -6)),
        'date_of_birth' => '2001-01-01',
    ], $overrides));

    return compact('tenant', 'student');
}

function createAdminExamResultsStaff(int $tenantId, array $permissions = ['viewStudentExamResults:students']): User
{
    $user = User::factory()->create(['tenant_id' => $tenantId]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

test('viewStudentExamResults permission is registered for staff', function () {
    expect(PermissionRegistry::allValues())->toContain('viewStudentExamResults:students')
        ->and(PermissionRegistry::allValues())->toContain('viewOwnExamResults:students');
});

test('admin exam results index requires viewStudentExamResults permission', function () {
    ['tenant' => $tenant, 'student' => $student] = createAdminExamResultsStudent();
    $staff = createAdminExamResultsStaff((int) $tenant->id, []);
    Sanctum::actingAs($staff);

    $this->getJson(route('v1.students.exam-results.index', $student->id))
        ->assertForbidden()
        ->assertJsonPath('message', __('examinations.exam_results_permission_denied'));
});

test('admin exam results index forbidden message is not Not Found', function () {
    ['tenant' => $tenant, 'student' => $student] = createAdminExamResultsStudent();
    $staff = createAdminExamResultsStaff((int) $tenant->id, []);
    Sanctum::actingAs($staff);

    $response = $this->getJson(route('v1.students.exam-results.index', $student->id));

    $response->assertForbidden();
    expect($response->json('message'))
        ->not->toBe('Not Found')
        ->not->toBe('')
        ->not->toBeNull();
});

test('admin exam results lookup denies staff without permission with explicit message', function () {
    ['tenant' => $tenant, 'student' => $student] = createAdminExamResultsStudent();
    $staff = createAdminExamResultsStaff((int) $tenant->id, []);
    Sanctum::actingAs($staff);

    $this->postJson(route('v1.students.exam-results.lookup', $student->id), [
        'candidate_number' => 'CAND-1',
    ])
        ->assertForbidden()
        ->assertJsonPath('message', __('examinations.exam_results_permission_denied'));
});

test('admin exam results index returns access payload for permitted staff', function () {
    ['tenant' => $tenant, 'student' => $student] = createAdminExamResultsStudent();
    $staff = createAdminExamResultsStaff((int) $tenant->id);
    Sanctum::actingAs($staff);

    StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND-ADMIN-1',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'AWARD',
    ]);

    $this->getJson(route('v1.students.exam-results.index', $student->id))
        ->assertOk()
        ->assertJsonPath('data.access.gate', 'fees')
        ->assertJsonPath('data.savedResults.0.candidateNumber', 'CAND-ADMIN-1');
});

test('admin exam results show is scoped to the route student', function () {
    ['tenant' => $tenant, 'student' => $student] = createAdminExamResultsStudent();
    $other = createAdminExamResultsStudent()['student'];

    $result = StudentExamResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $other->id,
        'candidate_number' => 'CAND-OTHER',
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'PROCEED',
    ]);

    $staff = createAdminExamResultsStaff((int) $tenant->id);
    Sanctum::actingAs($staff);

    $this->getJson(route('v1.students.exam-results.show', [$student->id, $result->id]))
        ->assertNotFound();
});

test('admin exam results lookup upserts a saved result for the student', function () {
    ['tenant' => $tenant, 'student' => $student] = createAdminExamResultsStudent([
        'student_number' => 'LOOKUP'.strtoupper(Str::random(4)),
    ]);

    $this->mock(StudentExamResultAccessService::class, function ($mock) {
        $mock->shouldReceive('evaluate')->andReturn([
            'canViewResults' => true,
            'gate' => 'fees',
            'allowOnlineClearance' => false,
            'fees' => [
                'tuition' => 100,
                'autoCardFee' => 0,
                'partTimeLevy' => 0,
                'expectedTotal' => 100,
                'paidFromBank' => 100,
                'paidFromLedger' => 0,
                'paidTotal' => 100,
                'outstanding' => 0,
                'isFullyPaid' => true,
                'breakdown' => [],
                'hasStudentNumber' => true,
                'source' => 'enrolment',
            ],
            'clearance' => null,
            'idValidation' => ['required' => false, 'isValid' => true, 'needsCorrection' => false],
            'academicCalendarId' => null,
            'semesterId' => null,
            'calendarType' => 'semester',
        ]);
        $mock->shouldReceive('resolveEnrolmentContext')->andReturn(null);
        $mock->shouldReceive('resolveCalendarType')->andReturn(
            AcademicCalendarTypeEnum::SEMESTER
        );
    });

    ExaminationResult::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'candidate_number' => $student->student_number,
        'subject_code' => 'SUB1',
        'subject' => 'Maths',
        'grade' => 'P',
        'session' => '2026-06-01',
        'session_date' => '2026-06-01',
        'course_comment' => 'AWARD',
    ]);

    $staff = createAdminExamResultsStaff((int) $tenant->id);
    Sanctum::actingAs($staff);

    $this->postJson(route('v1.students.exam-results.lookup', $student->id), [
        'candidate_number' => $student->student_number,
    ])->assertOk()
        ->assertJsonPath('data.found', true)
        ->assertJsonPath('data.summary.candidateNumber', $student->student_number);

    expect(
        StudentExamResult::query()
            ->where('student_id', $student->id)
            ->where('candidate_number', $student->student_number)
            ->exists()
    )->toBeTrue();
});
