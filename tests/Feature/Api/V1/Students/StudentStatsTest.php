<?php

use App\Models\Institution\Staff;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

require_once __DIR__.'/StudentIndexFilterTest.php';

it('returns aggregated student stats for enrolled students', function (): void {
    $maleProgram = createVerifiedStudentApplication('STU-STAT-M-'.strtoupper(str()->random(4)));
    $femaleProgram = createVerifiedStudentApplication('STU-STAT-F-'.strtoupper(str()->random(4)));

    $femaleGender = Gender::query()->firstOrCreate(['title' => 'Female']);
    $femaleProgram->student->update(['gender_id' => $femaleGender->id]);

    createStudentEnrolmentForProgram($maleProgram);
    createStudentEnrolmentForProgram($femaleProgram);

    $user = User::factory()->create(['tenant_id' => $maleProgram->tenant_id]);
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.students.stats'));
    $response->assertOk()
        ->assertJsonPath('global.total', 2)
        ->assertJsonPath('global.male', 1)
        ->assertJsonPath('global.female', 1)
        ->assertJsonPath('filtered.total', 2);

    $levelNames = collect($response->json('global.byLevel'))->pluck('name')->all();
    expect($levelNames)->not->toBeEmpty();

    $modeNames = collect($response->json('global.byModeOfStudy'))->pluck('name')->all();
    expect($modeNames)->toContain('Full Time');
});

it('returns a lower filtered total when gender filter is applied', function (): void {
    $maleProgram = createVerifiedStudentApplication('STU-STAT-GM-'.strtoupper(str()->random(4)));
    $femaleProgram = createVerifiedStudentApplication('STU-STAT-GF-'.strtoupper(str()->random(4)));

    $femaleGender = Gender::query()->firstOrCreate(['title' => 'Female']);
    $femaleProgram->student->update(['gender_id' => $femaleGender->id]);

    createStudentEnrolmentForProgram($maleProgram);
    createStudentEnrolmentForProgram($femaleProgram);

    $user = User::factory()->create(['tenant_id' => $maleProgram->tenant_id]);
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.students.stats').'?gender=male');
    $response->assertOk()
        ->assertJsonPath('global.total', 2)
        ->assertJsonPath('filtered.total', 1);
});

it('requires authentication for student stats', function (): void {
    $this->getJson(route('v1.students.stats'))
        ->assertUnauthorized();
});

it('restricts student stats to the department user own departments', function (): void {
    $ownProgram = createVerifiedStudentApplication('STU-STAT-OWN-'.strtoupper(str()->random(4)));
    $otherProgram = createVerifiedStudentApplication('STU-STAT-OTH-'.strtoupper(str()->random(4)));

    createStudentEnrolmentForProgram($ownProgram);
    createStudentEnrolmentForProgram($otherProgram);

    $departmentUser = User::factory()->create(['tenant_id' => $ownProgram->tenant_id]);
    $departmentUser->givePermissionTo('viewOnlyOwnDepartment:departments');

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);

    $staff = Staff::query()->create([
        'tenant_id' => $ownProgram->tenant_id,
        'user_id' => $departmentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
    ]);

    $staff->institutionDepartments()->attach($ownProgram->institution_department_id);

    Sanctum::actingAs($departmentUser);

    $response = $this->getJson(route('v1.students.stats'));
    $response->assertOk()
        ->assertJsonPath('global.total', 1)
        ->assertJsonPath('filtered.total', 1);
});
