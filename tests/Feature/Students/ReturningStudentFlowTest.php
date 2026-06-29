<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\Acl\Role;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\NextOfKin;
use App\Models\Shared\Relationship;
use App\Models\Shared\FeeType;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Integrations\OnlinePaymentContextResolver;
use App\Services\Students\ReturningStudentApplicationPrefillService;
use App\Services\Students\ReturningStudentContextService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function returningStudentTestLevel(): Level
{
    return Level::query()->firstOrCreate(
        ['name' => 'Returning Student Test Level'],
        [
            'description' => 'Test level',
            'position' => 99,
            'show_on_current_application_period' => 1,
            'has_application_fee_payment' => true,
        ],
    );
}

function createReturningStudentUser(array $studentAttributes = []): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $student = Student::query()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->value('id') ?? DB::table('titles')->insertGetId([
            'name' => 'Mr', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->value('id') ?? DB::table('genders')->insertGetId([
            'title' => 'Male', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->value('id') ?? DB::table('marital_statuses')->insertGetId([
            'title' => 'Single', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->value('id') ?? DB::table('id_types')->insertGetId([
            'name' => 'National ID', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'id_number' => '55-'.uniqid().'C55',
        'student_number' => 'SN-'.uniqid(),
    ], $studentAttributes));

    return [$user, $student];
}

test('returning student context detects can start application for rejected applicant without enrolment', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser();

    $service = app(ReturningStudentContextService::class);

    expect($service->canStartApplication($student->fresh()))->toBeTrue();
    expect($service->canContinueInClass($student->fresh()))->toBeFalse();
});

test('returning student prefill includes profile identity fields', function () {
    [$user, $student] = createReturningStudentUser([
        'id_number' => '55-1234567C55',
    ]);

    $prefill = app(ReturningStudentApplicationPrefillService::class)->build($student->fresh(['user', 'title', 'gender']));

    expect($prefill['first_name'])->toBe($user->first_name);
    expect($prefill['id_number'])->toBe('55-1234567C55');
    expect($prefill['title'])->toMatchArray([
        'value' => $student->title_id,
        'label' => $student->title?->name,
    ]);
    expect($prefill['gender'])->toMatchArray([
        'value' => $student->gender_id,
        'label' => $student->gender?->title,
    ]);
});

test('returning student prefill includes next of kin contact and address from relations', function () {
    [$user, $student] = createReturningStudentUser();

    $relationship = Relationship::query()->firstOrCreate(['name' => 'Guardian']);

    $nextOfKin = NextOfKin::query()->forceCreate([
        'tenant_id' => $student->tenant_id,
        'kinnable_id' => $student->id,
        'kinnable_type' => $student->getMorphClass(),
        'name' => 'Tinomuda Jonga',
        'relationship_id' => $relationship->id,
    ]);

    Contact::query()->create([
        'tenant_id' => $student->tenant_id,
        'contactable_id' => $nextOfKin->id,
        'contactable_type' => $nextOfKin->getMorphClass(),
        'phone_number' => '0778307175',
        'contact_is_main' => true,
    ]);

    Address::query()->create([
        'tenant_id' => $student->tenant_id,
        'addressable_id' => $nextOfKin->id,
        'addressable_type' => $nextOfKin->getMorphClass(),
        'address_1' => '89 Vito',
        'address_2' => 'Mbare National',
        'address_3' => 'Harare',
        'address_4' => '1604 Westwood',
        'address_is_main' => true,
    ]);

    $prefill = app(ReturningStudentApplicationPrefillService::class)->build($student->fresh());

    expect($prefill['next_of_kin_name'])->toBe('Tinomuda Jonga');
    expect($prefill['next_of_kin_phone_number'])->toBe('0778307175');
    expect($prefill['next_of_kin_address_1'])->toBe('89 Vito');
    expect($prefill['next_of_kin_address_2'])->toBe('Mbare National');
    expect($prefill['next_of_kin_address_3'])->toBe('Harare');
    expect($prefill['relationship'])->toMatchArray([
        'value' => $relationship->id,
        'label' => 'Guardian',
    ]);
});

test('profile applications page includes application hub for eligible student', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser();

    $this->actingAs($user)
        ->get(route('portal.profile.applications'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/profile/Section')
            ->where('activeTab', 'applications')
            ->has('applicationHub')
            ->where('applicationHub.canStartApplication', true));
});

test('application hub acknowledge persists metadata and redirects to applications', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser();

    $this->actingAs($user)
        ->post(route('portal.profile.applications.acknowledge'), [
            'intake_period_id' => $intake->id,
            'acknowledged' => true,
        ])
        ->assertRedirect(route('portal.profile.applications'));

    $student->refresh();
    expect($student->meta_data['returning_student']['path'])->toBe('reapply');
    expect((int) $student->meta_data['returning_student']['intake_period_id'])->toBe($intake->id);
});

test('post payment route for application fee sends profile students to applications', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user] = createReturningStudentUser();

    $level = returningStudentTestLevel();

    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::APPLICATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::APPLICATION_FEE->name(),
            'description' => FeeTypeEnum::APPLICATION_FEE->description(),
            'position' => FeeTypeEnum::APPLICATION_FEE->position(),
        ],
    );

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
    ]);

    $ledger = Ledger::query()->create([
        'tenant_id' => $user->tenant_id,
        'ledgerable_type' => ApplicationFee::class,
        'ledgerable_id' => $applicationFee->id,
        'fee_type_id' => $feeType->id,
        'intake_period_id' => $intake->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 20,
        'system_reference' => 'ORD-RETURNING-'.uniqid(),
        'payment_gateway' => 'smile-n-pay',
    ]);

    $this->actingAs($user);

    $url = app(OnlinePaymentContextResolver::class)->postPaymentRouteForLedger($ledger->load('feeType'));

    expect($url)->toBe(route('portal.profile.applications', ['fee_paid' => 1]));
});

test('acknowledged returning student can open profile level selection page', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser();

    app(ReturningStudentContextService::class)->persistAcknowledgement($student, 'reapply', $intake);

    $this->actingAs($user)
        ->get(route('portal.profile.applications.level'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/SelectLevelOption')
            ->where('selectLevelRoute', 'portal.profile.applications.select-level'));
});

test('unacknowledged returning student is redirected from profile level selection page', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user] = createReturningStudentUser();

    $this->actingAs($user)
        ->get(route('portal.profile.applications.level'))
        ->assertRedirect(route('portal.profile.applications'));
});

test('returning student prefill includes programme labels from prior application', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser();

    $institutionDepartmentId = DB::table('institution_departments')->value('id');
    $departmentLevelId = DB::table('department_levels')->value('id');
    $departmentCourseId = DB::table('department_courses')->value('id');
    $modeOfStudyId = DB::table('mode_of_studies')->value('id') ?? 1;

    if (! $institutionDepartmentId || ! $departmentLevelId || ! $departmentCourseId) {
        $this->markTestSkipped('Institution program structure not seeded.');
    }

    StudentApplication::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'intake_period_id' => $intake->id,
        'institution_department_id' => $institutionDepartmentId,
        'department_level_id' => $departmentLevelId,
        'department_course_id' => $departmentCourseId,
        'mode_of_study_id' => $modeOfStudyId,
    ]);

    $prefill = app(ReturningStudentApplicationPrefillService::class)->build($student->fresh());

    expect($prefill['department_id'])->toBe($institutionDepartmentId);
    expect($prefill['department'])->toHaveKeys(['value', 'label']);
    expect($prefill['level'])->toHaveKeys(['value', 'label']);
    expect($prefill['course'])->toHaveKeys(['value', 'label']);
    expect($prefill['modeOfStudy'])->toHaveKeys(['value', 'label']);
});

test('profile student with paid fee and acknowledgement can open returning wizard', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser();

    $level = returningStudentTestLevel();

    app(ReturningStudentContextService::class)->persistAcknowledgement($student, 'reapply', $intake);

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
    ]);

    $this->actingAs($user)
        ->get(route('portal.application.returning'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/ReturningApplication')
            ->where('studentId', $student->id)
            ->has('returningPrefill.title')
            ->has('returningPrefill.gender'));
});

test('continue in class page is accessible for eligible student', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser(['student_number' => '2025ENG001HP']);

    $acceptedStep = WorkflowStep::query()->firstOrCreate(['slug' => WorkflowStepEnum::ACCEPTED->slug()], [
        'name' => WorkflowStepEnum::ACCEPTED->name(),
    ]);

    $institutionDepartmentId = DB::table('institution_departments')->value('id');
    $departmentLevelId = DB::table('department_levels')->value('id');
    $departmentCourseId = DB::table('department_courses')->value('id');
    $modeOfStudyId = DB::table('mode_of_studies')->value('id') ?? 1;

    if (! $institutionDepartmentId || ! $departmentLevelId || ! $departmentCourseId) {
        $this->markTestSkipped('Institution program structure not seeded.');
    }

    $departmentStep = DepartmentApplicationStep::query()->firstOrCreate([
        'institution_department_id' => $institutionDepartmentId,
        'workflow_step_id' => $acceptedStep->id,
    ], [
        'position' => 1,
    ]);

    $application = StudentApplication::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'intake_period_id' => $intake->id,
        'institution_department_id' => $institutionDepartmentId,
        'department_level_id' => $departmentLevelId,
        'department_course_id' => $departmentCourseId,
        'mode_of_study_id' => $modeOfStudyId,
        'department_application_step_id' => $departmentStep->id,
    ]);

    ClassList::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_application_id' => $application->id,
        'type' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $this->actingAs($user)
        ->get(route('portal.returning-student.continue.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/returning-student/ContinueInClass'));
});

test('returning student continuation rejects mismatched student number', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$user, $student] = createReturningStudentUser(['student_number' => '2025ENG001HP']);

    $acceptedStep = WorkflowStep::query()->firstOrCreate(['slug' => WorkflowStepEnum::ACCEPTED->slug()], [
        'name' => WorkflowStepEnum::ACCEPTED->name(),
    ]);

    $institutionDepartmentId = DB::table('institution_departments')->value('id');
    $departmentLevelId = DB::table('department_levels')->value('id');
    $departmentCourseId = DB::table('department_courses')->value('id');
    $modeOfStudyId = DB::table('mode_of_studies')->value('id') ?? 1;

    if (! $institutionDepartmentId || ! $departmentLevelId || ! $departmentCourseId) {
        $this->markTestSkipped('Institution program structure not seeded.');
    }

    $departmentStep = DepartmentApplicationStep::query()->firstOrCreate([
        'institution_department_id' => $institutionDepartmentId,
        'workflow_step_id' => $acceptedStep->id,
    ], [
        'position' => 1,
    ]);

    $application = StudentApplication::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'intake_period_id' => $intake->id,
        'institution_department_id' => $institutionDepartmentId,
        'department_level_id' => $departmentLevelId,
        'department_course_id' => $departmentCourseId,
        'mode_of_study_id' => $modeOfStudyId,
        'department_application_step_id' => $departmentStep->id,
    ]);

    ClassList::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_application_id' => $application->id,
        'type' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $this->actingAs($user)
        ->post(route('portal.returning-student.continue'), [
            'intake_period_id' => $intake->id,
            'student_number' => 'WRONG-NUMBER',
            'acknowledged' => true,
        ])
        ->assertSessionHasErrors('student_number');
});
