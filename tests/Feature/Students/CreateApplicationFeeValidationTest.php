<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Http\Requests\Students\UpdateReturningApplicationRequest;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Rbac\Role;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

if (! function_exists('createReturningStudentUser')) {
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
}

function validateReturningApplicationFee(User $user, array $overrides = []): Illuminate\Validation\Validator
{
    $data = array_merge([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'gender_id' => 1,
        'marital_status_id' => 1,
        'title_id' => 1,
        'mode_of_study_id' => 1,
        'id_type_id' => 1,
        'id_number' => '631234567A63',
        'address_1' => 'Line 1',
        'address_2' => 'Line 2',
        'address_3' => 'Line 3',
        'email' => 'student@example.com',
        'phone_number' => '0777000000',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_address_1' => 'Kin 1',
        'next_of_kin_address_2' => 'Kin 2',
        'next_of_kin_address_3' => 'Kin 3',
        'relationship_id' => 1,
        'next_of_kin_phone_number' => '0777111111',
        'disability_status' => 'no',
    ], $overrides);

    $request = UpdateReturningApplicationRequest::create('/portal/application/returning', 'POST', $data);
    $request->setUserResolver(fn () => $user);

    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    return $validator;
}

function createApplicationFeeValidationFixture(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
    ]);

    $paidLevel = Level::query()->create([
        'name' => 'Paid Validation Level '.uniqid(),
        'description' => 'Paid level',
        'position' => 97,
        'show_on_current_application_period' => true,
        'has_application_fee_payment' => true,
    ]);

    $alternateLevel = Level::query()->create([
        'name' => 'Alternate Validation Level '.uniqid(),
        'description' => 'Alternate level',
        'position' => 96,
        'show_on_current_application_period' => true,
        'has_application_fee_payment' => true,
    ]);

    $paidDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $paidLevel->id,
    ]);

    $alternateDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $alternateLevel->id,
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $mode = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::FULL_TIME->value],
        ['description' => 'Full Time'],
    );

    foreach ([$paidDepartmentLevel, $alternateDepartmentLevel] as $departmentLevel) {
        DepartmentLevelCourse::query()->firstOrCreate([
            'department_level_id' => $departmentLevel->id,
            'department_course_id' => $departmentCourse->id,
        ]);

        seedApplicationOffering(
            $institutionDepartment,
            $departmentLevel,
            $departmentCourse,
            [(int) $mode->id],
        );
    }

    return [
        $tenant,
        $institutionDepartment,
        $paidLevel,
        $alternateDepartmentLevel,
        $paidDepartmentLevel,
        $departmentCourse,
    ];
}

test('paid application fee allows a different current intake department level', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$tenant, $institutionDepartment, $paidLevel, $alternateDepartmentLevel, , $departmentCourse] = createApplicationFeeValidationFixture();
    [$user, $student] = createReturningStudentUser();

    ApplicationFee::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $paidLevel->id,
        'status' => ApplicationFeeStatusEnum::PAID,
    ]);

    $validator = validateReturningApplicationFee($user, [
        'gender_id' => $student->gender_id,
        'marital_status_id' => $student->marital_status_id,
        'title_id' => $student->title_id,
        'id_type_id' => $student->id_type_id,
        'id_number' => $student->id_number,
        'department_id' => $institutionDepartment->id,
        'level_id' => $alternateDepartmentLevel->id,
        'course_id' => $departmentCourse->id,
    ]);

    expect($validator->errors()->has('level_id'))->toBeFalse();
    expect(PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intake))->toBeTrue();
});

test('unpaid fee-required level is rejected during returning application validation', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [$tenant, $institutionDepartment, , , $paidDepartmentLevel, $departmentCourse] = createApplicationFeeValidationFixture();
    [$user, $student] = createReturningStudentUser();

    $validator = validateReturningApplicationFee($user, [
        'gender_id' => $student->gender_id,
        'marital_status_id' => $student->marital_status_id,
        'title_id' => $student->title_id,
        'id_type_id' => $student->id_type_id,
        'id_number' => $student->id_number,
        'department_id' => $institutionDepartment->id,
        'level_id' => $paidDepartmentLevel->id,
        'course_id' => $departmentCourse->id,
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('level_id'))->toBe(__('trans.application_fee_payment_required'));
});

test('application fee exempt user passes fee validation without payment', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    [, $institutionDepartment, , , $paidDepartmentLevel, $departmentCourse] = createApplicationFeeValidationFixture();
    [$user, $student] = createReturningStudentUser();
    $user->forceFill(['email' => 'teststundent@system.com'])->save();

    $validator = validateReturningApplicationFee($user, [
        'gender_id' => $student->gender_id,
        'marital_status_id' => $student->marital_status_id,
        'title_id' => $student->title_id,
        'id_type_id' => $student->id_type_id,
        'id_number' => $student->id_number,
        'department_id' => $institutionDepartment->id,
        'level_id' => $paidDepartmentLevel->id,
        'course_id' => $departmentCourse->id,
    ]);

    expect($validator->errors()->has('level_id'))->toBeFalse();
});
