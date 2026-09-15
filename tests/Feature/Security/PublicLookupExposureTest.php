<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Auth;

/*
 * Public and lightly protected lookups must not reveal whether staff or student records exist, or leak
 * staff email addresses, to people who are not signed in.
 */

test('guests may only run the uniqueness checks registration needs', function () {
    $this->getJson(route('v1.check', ['key' => 'user_email', 'value' => 'nobody-'.uniqid().'@example.com']))
        ->assertOk()
        ->assertJsonPath('available', true);

    foreach (['user_phone_number', 'staff_employee_number', 'staff_national_id', 'staff_passport_number', 'student_number'] as $key) {
        $this->getJson(route('v1.check', ['key' => $key, 'value' => 'ANY-VALUE']))
            ->assertUnauthorized();
    }
});

test('signed-in users can run staff uniqueness checks', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('v1.check', ['key' => 'staff_employee_number', 'value' => 'EMP-'.uniqid()]))
        ->assertOk()
        ->assertJsonPath('available', true);
});

test('dashboard metrics api requires dashboard access', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('v1.institution.dashboard.metrics'))
        ->assertForbidden();

    enableDashboardModule();

    $this->actingAs(userWithDashboardPermission())
        ->postJson(route('v1.institution.dashboard.metrics'))
        ->assertOk()
        ->assertJsonStructure(['departmentDistribution']);
});

test('guests cannot find academic staff by email', function () {
    $ctx = makeSyllabusModuleContext();
    $lecturer = makeSyllabusModuleLecturerStaff($ctx);
    $email = (string) $lecturer->user?->email;

    $staffIds = fn ($response): array => collect($response->json('data'))
        ->flatMap(fn (array $group) => collect($group['staff'])->pluck('id'))
        ->all();

    $signedIn = $this->actingAs($ctx['user'])
        ->getJson(route('v1.academic-staff.grouped-by-department', ['search' => $email]))
        ->assertOk();

    expect($staffIds($signedIn))->toContain($lecturer->id);

    Auth::forgetGuards();

    $guest = $this->getJson(route('v1.academic-staff.grouped-by-department', ['search' => $email]))
        ->assertOk();

    expect($staffIds($guest))->not->toContain($lecturer->id);
});
