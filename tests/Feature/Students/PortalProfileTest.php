<?php

use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

test('portal profile personal information route renders for authorized student', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Portal',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Portal',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Portal',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Portal',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-PROFILE-001',
    ]);

    $response = $this->actingAs($user)->get(route('portal.profile.personal-information'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/student/profile/Section')
        ->where('activeTab', 'basic_info')
        ->where('student.id', $student->id));
});

test('portal profile accommodations route renders for authorized student', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentAccommodationDetails:students');

    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Accommodations',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Accommodations',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Accommodations',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Accommodations',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-ACCOMM-001',
    ]);

    $response = $this->actingAs($user)->get(route('portal.profile.accommodations'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/student/profile/Section')
        ->where('activeTab', 'accommodations')
        ->where('student.id', $student->id));
});

test('portal profile accommodations pay route renders for authorized student', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentAccommodationDetails:students');

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Accommodations Pay',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Accommodations Pay',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Accommodations Pay',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Accommodations Pay',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-ACCOMM-PAY-001',
    ]);

    $response = $this->actingAs($user)->get(route('portal.profile.accommodations.pay'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/student/profile/AccommodationFeePaymentOptions')
        ->has('fees')
        ->where('fees.due', fn ($due) => is_string($due) || is_numeric($due)));
});

test('portal profile accommodations pay route exposes fee structure amount when no ledger exists', function () {
    $tenant = Tenant::query()->firstOrFail();
    $studentProgram = createStudentReadyForHostelApplication('PORTAL-ACCOMM-PAY-002');
    $student = $studentProgram->student;
    $user = User::query()->findOrFail($student->user_id);
    $user->givePermissionTo('manageOwnStudentAccommodationDetails:students');

    $feeType = \App\Models\Shared\FeeType::query()->firstOrCreate(
        ['slug' => \App\Enums\Shared\FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug()],
        [
            'name' => \App\Enums\Shared\FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->name(),
            'description' => \App\Enums\Shared\FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->description(),
            'position' => \App\Enums\Shared\FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->position(),
        ],
    );

    \App\Models\Institution\FeeStructure::query()->create([
        'tenant_id' => $tenant->id,
        'fee_type_id' => $feeType->id,
        'level_id' => $studentProgram->departmentLevel->level_id,
        'mode_of_study_id' => $studentProgram->mode_of_study_id,
        'amount' => 150.00,
        'local_fca_amount' => 250.00,
    ]);

    $response = $this->actingAs($user)->get(route('portal.profile.accommodations.pay'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/student/profile/AccommodationFeePaymentOptions')
        ->where('fees.due', '250.00')
        ->where('fees.total', '250.00')
        ->where('accommodationFee.attributes.localFcaAmount', fn ($amount) => (float) $amount === 250.0));
});

test('legacy portal personal details route redirects to profile personal information', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-REDIRECT-001',
    ]);

    $this->actingAs($user)
        ->get(route('portal.personal-details'))
        ->assertRedirect(route('portal.profile.personal-information'));
});

test('portal student can update own login credentials', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'portal.student@example.com',
        'password' => 'OldPassword1!',
    ]);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Credentials',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Credentials',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Credentials',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Credentials',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-CREDENTIALS-001',
    ]);

    $this->actingAs($user)
        ->put(route('users.update-user-credentials', ['user' => $user->id]), [
            'change_email' => true,
            'change_password' => false,
            'email' => 'portal.student.updated@example.com',
        ])
        ->assertOk();

    expect($user->fresh()->email)->toBe('portal.student.updated@example.com');
});

test('portal student cannot update another users login credentials', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $otherUser = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->put(route('users.update-user-credentials', ['user' => $otherUser->id]), [
            'change_email' => true,
            'change_password' => false,
            'email' => 'hijacked@example.com',
        ])
        ->assertForbidden();
});
