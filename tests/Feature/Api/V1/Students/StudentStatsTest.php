<?php

use App\Enums\Shared\DisabilityStatusEnum;
use App\Models\Institution\Staff;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentSponsor;
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

    $typeIds = collect($response->json('global.byStudentType'))->pluck('id')->all();
    expect($typeIds)->toContain('direct', 'apprentice');

    $sponsoredIds = collect($response->json('global.bySponsored'))->pluck('id')->all();
    expect($sponsoredIds)->toContain('sponsored', 'not_sponsored');

    $disabilityIds = collect($response->json('global.byDisability'))->pluck('id')->all();
    expect($disabilityIds)->toContain('yes', 'no');
});

it('returns student type breakdown counts in stats', function (): void {
    $directProgram = createVerifiedStudentApplication('STU-STAT-D-'.strtoupper(str()->random(4)));
    $apprenticeProgram = createVerifiedStudentApplication('STU-STAT-A-'.strtoupper(str()->random(4)));

    createStudentEnrolmentForProgram($directProgram);
    createStudentEnrolmentForProgram($apprenticeProgram);

    StudentApprentice::query()->create([
        'tenant_id' => $apprenticeProgram->tenant_id,
        'student_id' => $apprenticeProgram->student_id,
        'calendar_year' => 2026,
    ]);

    $user = User::factory()->create(['tenant_id' => $directProgram->tenant_id]);
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.students.stats'));
    $response->assertOk();

    $byType = collect($response->json('global.byStudentType'))->keyBy('id');

    expect($byType->get('direct')['count'])->toBe(1)
        ->and($byType->get('apprentice')['count'])->toBe(1);
});

it('returns sponsored breakdown counts in stats', function (): void {
    $sponsoredProgram = createVerifiedStudentApplication('STU-STAT-S-'.strtoupper(str()->random(4)));
    $notSponsoredProgram = createVerifiedStudentApplication('STU-STAT-NS-'.strtoupper(str()->random(4)));

    createStudentEnrolmentForProgram($sponsoredProgram);
    createStudentEnrolmentForProgram($notSponsoredProgram);

    StudentSponsor::query()->create([
        'tenant_id' => $sponsoredProgram->tenant_id,
        'student_id' => $sponsoredProgram->student_id,
        'calendar_year' => 2026,
        'sponsor' => 'Stats Sponsor',
    ]);

    $user = User::factory()->create(['tenant_id' => $sponsoredProgram->tenant_id]);
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.students.stats'));
    $response->assertOk();

    $bySponsored = collect($response->json('global.bySponsored'))->keyBy('id');

    expect($bySponsored->get('sponsored')['count'])->toBe(1)
        ->and($bySponsored->get('not_sponsored')['count'])->toBe(1);
});

it('returns disability breakdown counts treating prefer_not_to_say and null as no', function (): void {
    $yesProgram = createVerifiedStudentApplication('STU-STAT-DY-'.strtoupper(str()->random(4)));
    $noProgram = createVerifiedStudentApplication('STU-STAT-DN-'.strtoupper(str()->random(4)));
    $preferProgram = createVerifiedStudentApplication('STU-STAT-DP-'.strtoupper(str()->random(4)));
    $nullProgram = createVerifiedStudentApplication('STU-STAT-DU-'.strtoupper(str()->random(4)));

    $yesProgram->student->update(['disability_status' => DisabilityStatusEnum::YES->value]);
    $noProgram->student->update(['disability_status' => DisabilityStatusEnum::NO->value]);
    $preferProgram->student->update(['disability_status' => DisabilityStatusEnum::PREFER_NOT_TO_SAY->value]);
    $nullProgram->student->update(['disability_status' => null]);

    createStudentEnrolmentForProgram($yesProgram);
    createStudentEnrolmentForProgram($noProgram);
    createStudentEnrolmentForProgram($preferProgram);
    createStudentEnrolmentForProgram($nullProgram);

    $user = User::factory()->create(['tenant_id' => $yesProgram->tenant_id]);
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.students.stats'));
    $response->assertOk();

    $byDisability = collect($response->json('global.byDisability'))->keyBy('id');

    expect($byDisability->get('yes')['count'])->toBe(1)
        ->and($byDisability->get('no')['count'])->toBe(3);
});

it('keeps global sponsored and disability chip counts when gender filter is applied', function (): void {
    $maleSponsored = createVerifiedStudentApplication('STU-STAT-MS-'.strtoupper(str()->random(4)));
    $femaleUnsponsored = createVerifiedStudentApplication('STU-STAT-FU-'.strtoupper(str()->random(4)));

    $femaleGender = Gender::query()->firstOrCreate(['title' => 'Female']);
    $femaleUnsponsored->student->update([
        'gender_id' => $femaleGender->id,
        'disability_status' => DisabilityStatusEnum::YES->value,
    ]);
    $maleSponsored->student->update(['disability_status' => DisabilityStatusEnum::NO->value]);

    createStudentEnrolmentForProgram($maleSponsored);
    createStudentEnrolmentForProgram($femaleUnsponsored);

    StudentSponsor::query()->create([
        'tenant_id' => $maleSponsored->tenant_id,
        'student_id' => $maleSponsored->student_id,
        'calendar_year' => 2026,
        'sponsor' => 'Chip Sponsor',
    ]);

    $user = User::factory()->create(['tenant_id' => $maleSponsored->tenant_id]);
    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.students.stats').'?gender=male');
    $response->assertOk()
        ->assertJsonPath('global.total', 2)
        ->assertJsonPath('filtered.total', 1);

    $bySponsored = collect($response->json('global.bySponsored'))->keyBy('id');
    $byDisability = collect($response->json('global.byDisability'))->keyBy('id');

    expect($bySponsored->get('sponsored')['count'])->toBe(1)
        ->and($bySponsored->get('not_sponsored')['count'])->toBe(1)
        ->and($byDisability->get('yes')['count'])->toBe(1)
        ->and($byDisability->get('no')['count'])->toBe(1);
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
