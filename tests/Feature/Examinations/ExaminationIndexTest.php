<?php

use App\Models\Examinations\ExaminationResult;
use App\Models\Users\User;
use Spatie\Permission\Models\Permission;

function createExaminationIndexUser(array $permissions = ['viewAny:examinations']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

it('defaults examination index to the latest session', function (): void {
    $user = createExaminationIndexUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '43000',
        'session_date' => '2020-01-01',
        'candidate_number' => 'OLD0001',
        'subject_code' => 'S-OLD',
        'surname' => 'Oldman',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'NEW0001',
        'subject_code' => 'S-NEW',
        'surname' => 'Newman',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Index')
            ->where('filters.session', '45000')
            ->has('results.data', 1)
            ->where('results.data.0.candidateNumber', 'NEW0001')
            ->where('results.data.0.surname', 'Newman'));
});

it('filters examination index by discipline surname and candidate number', function (): void {
    $user = createExaminationIndexUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Automotive',
        'candidate_number' => 'AUTO001',
        'surname' => 'Gwatumba',
        'first_names' => 'Sihle',
        'subject_code' => '306/13/S01',
        'subject' => 'Automobile Electrics',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'discipline' => 'Civil',
        'candidate_number' => 'CIV001',
        'surname' => 'Moyo',
        'first_names' => 'Tariro',
        'subject_code' => '301/13/S01',
        'subject' => 'Structures',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.index', [
            'session' => '45000',
            'discipline' => 'Automotive',
            'surname' => 'Gwat',
            'candidate_number' => 'AUTO',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Index')
            ->has('results.data', 1)
            ->where('results.data.0.candidateNumber', 'AUTO001')
            ->where('filters.discipline', 'Automotive'));
});

it('filters examination index by subject code and first names', function (): void {
    $user = createExaminationIndexUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'A001',
        'first_names' => 'Sihle',
        'subject_code' => '306/13/S01',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'B001',
        'first_names' => 'Tariro',
        'subject_code' => '306/13/S02',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.index', [
            'session' => '45000',
            'subject_code' => '306/13/S01',
            'first_names' => 'Sih',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
            ->where('results.data.0.candidateNumber', 'A001'));
});

it('does not apply the legacy catch-all search parameter', function (): void {
    $user = createExaminationIndexUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'KEEP001',
        'surname' => 'Keeper',
        'subject_code' => 'S01',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'session' => '45000',
        'session_date' => '2024-06-01',
        'candidate_number' => 'HIDE001',
        'surname' => 'Hidden',
        'subject_code' => 'S02',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.index', [
            'session' => '45000',
            'search' => 'Hidden',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('results.data', 2));
});

it('paginates filtered examination results', function (): void {
    $user = createExaminationIndexUser();

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'AAA0001',
        'surname' => 'Alpha',
        'subject_code' => 'AAA-S01',
        'session' => '44287',
        'session_date' => '2021-04-01',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'AAA0001',
        'surname' => 'Alpha',
        'subject_code' => 'AAA-S02',
        'session' => '44287',
        'session_date' => '2021-04-01',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'BBB0001',
        'surname' => 'Beta',
        'subject_code' => 'BBB-S01',
        'session' => '44287',
        'session_date' => '2021-04-01',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'BBB0001',
        'surname' => 'Beta',
        'subject_code' => 'BBB-S02',
        'session' => '44287',
        'session_date' => '2021-04-01',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.index', ['page_size' => 2, 'session' => '44287']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Index')
            ->has('results.data', 2)
            ->where('results.meta.current_page', 1)
            ->where('results.meta.per_page', 2)
            ->where('results.meta.total', 4)
            ->where('results.meta.last_page', 2)
            ->where('results.data.0.candidateNumber', 'AAA0001'));
});

it('forbids examination index without permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('examinations.index'))
        ->assertForbidden();
});

it('includes canImport when user may import', function (): void {
    $user = createExaminationIndexUser(['viewAny:examinations', 'import:examinations']);

    $this->actingAs($user)
        ->get(route('examinations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('canImport', true));
});
