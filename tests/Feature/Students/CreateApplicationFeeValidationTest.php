<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Http\Requests\Students\UpdateReturningApplicationRequest;
use App\Models\Acl\Role;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Students\ApplicationFee;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

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
        'show_on_current_application_period' => true,
    ]);

    $alternateDepartmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $alternateLevel->id,
        'show_on_current_application_period' => true,
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
        'show_on_current_application_period' => true,
    ]);

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
