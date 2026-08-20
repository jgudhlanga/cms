<?php

use App\Enums\Shared\IdTypeEnum;
use App\Models\Examinations\ExaminationResult;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentExamResult;
use App\Models\Users\User;
use Spatie\Permission\Models\Permission;

function createExaminationDashboardUser(array $permissions = ['viewAny:examinations']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

test('guests are redirected when visiting examination dashboard', function (): void {
    $this->get(route('examinations.dashboard'))->assertRedirect('/login');
});

test('authenticated users without examination permissions cannot visit dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('examinations.dashboard'))->assertForbidden();
});

it('defaults dashboard to the latest session and counts unique candidates', function (): void {
    $user = createExaminationDashboardUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '43000',
        'session_date' => '2020-01-01',
        'candidate_number' => 'OLD001',
        'subject_code' => 'S01',
        'course_comment' => 'AWARD',
    ]);

    // Same candidate, two subjects — should count once as AWARD.
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'CAND001',
        'subject_code' => 'S01',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'CAND001',
        'subject_code' => 'S02',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'CAND002',
        'subject_code' => 'S01',
        'course_comment' => 'PROCEED',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'CAND003',
        'subject_code' => 'S01',
        'course_comment' => 'REFERRED',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'CAND004',
        'subject_code' => 'S01',
        'course_comment' => 'ABSENT',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Dashboard')
            ->where('filters.session', '45000')
            ->where('totalCandidates', 4)
            ->where('statusCounts.AWARD', 1)
            ->where('statusCounts.PROCEED', 1)
            ->where('statusCounts.REFERRED', 1)
            ->where('statusCounts.ABSENT', 1)
            ->where('passRate', 50)
            ->where('onlineViewedCount', 0)
            ->where('onlineViewedRate', 0)
            ->where('statusLabels.AWARD', 'Award')
            ->where('statusLabels.PROCEED', 'Proceed')
            ->where('comparison', null));
});

it('counts students who viewed results online for the selected session', function (): void {
    $user = createExaminationDashboardUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'CAND001',
        'subject_code' => 'S01',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'CAND002',
        'subject_code' => 'S01',
        'course_comment' => 'PROCEED',
    ]);

    $suffix = uniqid();
    $studentUser = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $idType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->label()],
    );
    $student = Student::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $studentUser->id,
        'title_id' => Title::query()->create(['name' => 'Mr Dash '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male Dash '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single Dash '.$suffix])->id,
        'id_type_id' => $idType->id,
        'passport_number' => 'P'.strtoupper(substr($suffix, -8)),
        'student_number' => 'H'.strtoupper(substr($suffix, -6)),
        'date_of_birth' => '2001-01-01',
    ]);

    StudentExamResult::query()->create([
        'tenant_id' => $user->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => 'CAND001',
        'calendar_year' => 2024,
        'session' => '45000',
        'comment' => 'AWARD',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.dashboard', ['session' => '45000']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('totalCandidates', 2)
            ->where('onlineViewedCount', 1)
            ->where('onlineViewedRate', 50));
});

it('includes comparison payload when compare session is set', function (): void {
    $user = createExaminationDashboardUser();

    // Primary session 45000: 2 pass of 2 = 100%
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'P001',
        'subject_code' => 'S01',
        'subject' => 'Module One',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'P002',
        'subject_code' => 'S01',
        'subject' => 'Module One',
        'course_comment' => 'PROCEED',
    ]);

    // Compare session 44000: 1 pass of 2 = 50%
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '44000',
        'session_date' => '2023-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'C001',
        'subject_code' => 'S01',
        'subject' => 'Module One',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '44000',
        'session_date' => '2023-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'C002',
        'subject_code' => 'S01',
        'subject' => 'Module One',
        'course_comment' => 'REFERRED',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.dashboard', [
            'session' => '45000',
            'compare_session' => '44000',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Dashboard')
            ->where('filters.compare_session', '44000')
            ->where('comparison.primaryPassRate', 100)
            ->where('comparison.comparePassRate', 50)
            ->has('comparison.modules', 1)
            ->where('comparison.modules.0.subjectCode', 'S01')
            ->where('comparison.modules.0.primaryPassRate', 100)
            ->where('comparison.modules.0.comparePassRate', 50)
            ->where('comparison.modules.0.delta', 50)
            ->where('comparison.modules.0.trend', 'improved'));
});

it('excludes primary session from compare session options', function (): void {
    $user = createExaminationDashboardUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'A1',
        'subject_code' => 'S01',
        'course_comment' => 'AWARD',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '44000',
        'session_date' => '2023-06-01',
        'candidate_number' => 'B1',
        'subject_code' => 'S01',
        'course_comment' => 'AWARD',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.dashboard', ['session' => '45000']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('filterOptions.compareSessions', 1)
            ->where('filterOptions.compareSessions.0.value', '44000'));
});
