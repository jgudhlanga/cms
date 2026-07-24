<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\Rbac\Role;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Support\Str;

require_once __DIR__.'/MaintenanceControllerTest.php';
require_once __DIR__.'/NonEnrolledStudentUsersTest.php';

beforeEach(function (): void {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function createNoProfileStudentUser(int $tenantId): User
{
    $studentUser = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Purge',
        'last_name' => 'Candidate',
    ]);
    assignStudentRole($studentUser);

    return $studentUser;
}

it('returns unauthorized for guests on single purge endpoint', function (): void {
    $user = User::factory()->create();

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $user))
        ->assertUnauthorized();
});

const PURGE_REASON = 'Valid maintenance purge reason for testing.';

it('forbids users without root manage from single purge endpoint', function (): void {
    $actor = User::factory()->create();
    $target = User::factory()->create(['tenant_id' => $actor->tenant_id]);

    $this->actingAs($actor)
        ->deleteJson(route('maintenance.non-enrolled-student-users.purge', $target), [
            'reason' => PURGE_REASON,
        ])
        ->assertForbidden();
});

it('purges a student user without a profile', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => PURGE_REASON,
    ])
        ->assertNoContent();

    expect(User::query()->whereKey($studentUser->id)->exists())->toBeFalse()
        ->and(User::withTrashed()->whereKey($studentUser->id)->exists())->toBeFalse();
});

it('purges a student user with application fees but no profile', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);

    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'name' => 'Purge Intake '.uniqid(),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYears(2)->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
    ]);

    $level = Level::factory()->create([
        'has_application_fee_payment' => true,
        'show_on_current_application_period' => true,
    ]);

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => $studentUser->id,
        'intake_period_id' => $intakePeriod->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => PURGE_REASON,
    ])
        ->assertNoContent();

    expect(User::query()->whereKey($studentUser->id)->exists())->toBeFalse()
        ->and(ApplicationFee::query()->whereKey($applicationFee->id)->exists())->toBeFalse();
});

it('rejects purging a student user with a profile', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $studentUser = User::factory()->create([
        'tenant_id' => $rootUser->tenant_id,
        'first_name' => 'Has',
        'last_name' => 'Profile',
    ]);
    assignStudentRole($studentUser);

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID']);

    Student::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'student_number' => 'PURGE-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => PURGE_REASON,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user');

    expect(User::query()->whereKey($studentUser->id)->exists())->toBeTrue();
});

it('rejects purging a user outside the maintenance list', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $program = createVerifiedStudentApplication('PURGE-OUT-'.strtoupper(Str::random(4)));
    $studentUser = $program->student->user;
    $studentUser->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($studentUser);
    createActiveEnrolmentForProgram($program);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $studentUser), [
        'reason' => PURGE_REASON,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user');

    expect(User::query()->whereKey($studentUser->id)->exists())->toBeTrue();
});

it('forbids purging a user from another tenant', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $otherTenantUser = User::factory()->create();
    assignStudentRole($otherTenantUser);

    $this->deleteJson(route('maintenance.non-enrolled-student-users.purge', $otherTenantUser))
        ->assertForbidden();

    expect(User::query()->whereKey($otherTenantUser->id)->exists())->toBeTrue();
});

it('bulk purges eligible users and skips ineligible ones', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $eligibleOne = createNoProfileStudentUser($rootUser->tenant_id);
    $eligibleTwo = createNoProfileStudentUser($rootUser->tenant_id);

    $program = createReviewStudentApplication('PURGE-BLK-'.strtoupper(Str::random(4)));
    $ineligible = $program->student->user;
    $ineligible->update(['tenant_id' => $rootUser->tenant_id]);
    assignStudentRole($ineligible);

    $response = $this->postJson(route('maintenance.non-enrolled-student-users.bulk-purge'), [
        'user_ids' => [$eligibleOne->id, $ineligible->id, $eligibleTwo->id],
        'reason' => PURGE_REASON,
    ]);

    $response->assertOk()
        ->assertJson([
            'purged' => [$eligibleOne->id, $eligibleTwo->id],
            'skipped' => [$ineligible->id],
        ]);

    expect(User::query()->whereKey($eligibleOne->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($eligibleTwo->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($ineligible->id)->exists())->toBeTrue();
});

it('forbids users without root manage from bulk purge endpoint', function (): void {
    $actor = User::factory()->create();
    $target = User::factory()->create(['tenant_id' => $actor->tenant_id]);
    assignStudentRole($target);

    $this->actingAs($actor)
        ->postJson(route('maintenance.non-enrolled-student-users.bulk-purge'), [
            'user_ids' => [$target->id],
            'reason' => PURGE_REASON,
        ])
        ->assertForbidden();
});

it('returns roles on non-enrolled student users list', function (): void {
    $rootUser = actingAsRootMaintenanceUser();
    $studentUser = createNoProfileStudentUser($rootUser->tenant_id);

    $response = $this->getJson(route('maintenance.non-enrolled-student-users'));
    $response->assertOk();

    $matched = collect($response->json('data'))->firstWhere('id', $studentUser->id);

    expect($matched['attributes']['roles'])->toBeArray()
        ->and(collect($matched['attributes']['roles'])->pluck('name'))->toContain(RoleEnum::STUDENT->name());
});
