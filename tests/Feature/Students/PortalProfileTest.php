<?php

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\FeeType;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
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

test('portal profile personal information exposes application summary when student has no enrolment', function () {
    $tenant = Tenant::query()->firstOrFail();
    $suffix = uniqid();

    $department = Department::factory()->create(['name' => 'Engineering Portal '.$suffix]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'portal-applicant-'.$suffix,
        'description' => 'Portal applicant profile test',
    ]);

    $course = Course::factory()->create(['name' => 'Computer Science Portal '.$suffix]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'National Certificate']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Portal Applicant']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 Portal Applicant',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $workflowStep = WorkflowStep::query()->firstOrCreate(
        ['slug' => WorkflowStepEnum::REVIEW->slug()],
        [
            'name' => WorkflowStepEnum::REVIEW->name(),
            'description' => WorkflowStepEnum::REVIEW->description(),
            'position' => WorkflowStepEnum::REVIEW->position(),
        ],
    );

    $departmentApplicationStep = DepartmentApplicationStep::query()->firstOrCreate(
        [
            'tenant_id' => $tenant->id,
            'institution_department_id' => $institutionDepartment->id,
            'workflow_step_id' => $workflowStep->id,
        ],
        ['position' => $workflowStep->position],
    );

    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Portal Applicant',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Portal Applicant',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Portal Applicant',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Portal Applicant',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-APPLICANT-001',
    ]);

    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'department_application_step_id' => $departmentApplicationStep->id,
    ]);

    $response = $this->actingAs($user)->get(route('portal.profile.personal-information'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/student/profile/Section')
        ->where('activeTab', 'basic_info')
        ->where('student.id', $student->id)
        ->where('student.attributes.level', 'National Certificate')
        ->where('student.attributes.course', $course->name)
        ->where('student.attributes.department', $department->name)
        ->where('student.attributes.modeOfStudy', 'Full Time Portal Applicant')
        ->where('student.attributes.applicationStatus', WorkflowStepEnum::REVIEW->name())
        ->where('student.attributes.intakePeriod', 'Semester 1 Portal Applicant')
        ->where('student.attributes.applicationTrackingNumber', $studentApplication->application_tracking_number)
        ->where('student.attributes.profileContext', 'applicant')
        ->where('student.attributes.enrolmentStatus', null));
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

test('portal profile accommodations pay route returns not found when fee structure is missing', function () {
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

    $response->assertRedirect(route('portal.profile.accommodations'));
    $response->assertSessionHas('error', __('students.accommodation_fee_payment_unavailable'));
});

test('portal profile accommodations pay route exposes fee structure amount when no ledger exists', function () {
    $tenant = Tenant::query()->firstOrFail();
    $studentApplication = createStudentReadyForHostelApplication('PORTAL-ACCOMM-PAY-002');
    $student = $studentApplication->student;
    $user = User::query()->findOrFail($student->user_id);
    $user->givePermissionTo('manageOwnStudentAccommodationDetails:students');

    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->name(),
            'description' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->description(),
            'position' => FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->position(),
        ],
    );

    FeeStructure::query()->create([
        'tenant_id' => $tenant->id,
        'fee_type_id' => $feeType->id,
        'level_id' => $studentApplication->departmentLevel->level_id,
        'mode_of_study_id' => null,
        'amount' => 150.00,
        'local_fca_amount' => 250.00,
    ]);

    $enrolment = $student->latestEnrolment ?? attachHostelApplicationEnrolment($studentApplication);

    HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'student_id' => $student->id,
        'student_enrolment_id' => $enrolment->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this->actingAs($user)->get(route('portal.profile.accommodations.pay'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/hms/AccommodationFeePaymentOptions')
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

test('portal student can update personal details via portal profile route', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $titleId = DB::table('titles')->insertGetId([
        'name' => 'Mr Portal Update',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $genderId = DB::table('genders')->insertGetId([
        'title' => 'Male Portal Update',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $maritalStatusId = DB::table('marital_statuses')->insertGetId([
        'title' => 'Single Portal Update',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $idTypeId = DB::table('id_types')->insertGetId([
        'name' => 'National ID Portal Update',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => $titleId,
        'gender_id' => $genderId,
        'marital_status_id' => $maritalStatusId,
        'id_type_id' => $idTypeId,
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-PROFILE-UPDATE-001',
        'disability_status' => 'no',
    ]);

    $this->actingAs($user)
        ->put(route('portal.profile.personal-information.update'), [
            'gender_id' => $genderId,
            'marital_status_id' => $maritalStatusId,
            'title_id' => $titleId,
            'id_type_id' => $idTypeId,
            'id_number' => '63-1234567N63',
            'date_of_birth' => '2000-01-01',
            'disability_status' => 'yes',
            'denomination' => 'Updated Denomination',
        ])
        ->assertOk();

    $student->refresh();

    expect($student->denomination)->toBe('Updated Denomination');
    expect($student->disability_status)->toBe('yes');
});

test('unauthorized user cannot update portal personal details', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $titleId = DB::table('titles')->insertGetId([
        'name' => 'Mr Portal Forbidden',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $genderId = DB::table('genders')->insertGetId([
        'title' => 'Male Portal Forbidden',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $maritalStatusId = DB::table('marital_statuses')->insertGetId([
        'title' => 'Single Portal Forbidden',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $idTypeId = DB::table('id_types')->insertGetId([
        'name' => 'National ID Portal Forbidden',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => $titleId,
        'gender_id' => $genderId,
        'marital_status_id' => $maritalStatusId,
        'id_type_id' => $idTypeId,
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-PROFILE-FORBIDDEN-001',
        'disability_status' => 'no',
    ]);

    $this->actingAs($user)
        ->put(route('portal.profile.personal-information.update'), [
            'gender_id' => $genderId,
            'marital_status_id' => $maritalStatusId,
            'title_id' => $titleId,
            'id_type_id' => $idTypeId,
            'id_number' => '63-1234567N63',
            'date_of_birth' => '2000-01-01',
            'disability_status' => 'no',
        ])
        ->assertForbidden();
});
