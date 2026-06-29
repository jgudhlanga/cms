<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\Acl\Role;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Database\Seeders\Acl\RoleGroupSeeder;
use Database\Seeders\Acl\RolesTableSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();
});

/**
 * @return array{
 *     user: User,
 *     staff: Staff,
 *     roleA: Role,
 *     roleB: Role,
 *     roleC: Role,
 *     institutionDepartmentOne: InstitutionDepartment,
 *     institutionDepartmentTwo: InstitutionDepartment,
 *     title: Title,
 *     gender: Gender,
 *     maritalStatus: MaritalStatus,
 *     employmentType: EmploymentType,
 * }
 */
function makeStaffUserUpdateContext(): array
{
    $tenant = Tenant::query()->firstOrFail();

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $employmentType = EmploymentType::query()->firstOrCreate([
        'name' => EmploymentTypeEnum::FULL_TIME->value,
    ], [
        'description' => EmploymentTypeEnum::FULL_TIME->description(),
    ]);

    $roleA = Role::query()->where('name', RoleEnum::HEAD_OF_DEPARTMENT->name())->firstOrFail();
    $roleB = Role::query()->where('name', RoleEnum::ACCOUNTANT_ASSISTANT->name())->firstOrFail();
    $roleC = Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail();

    $departmentOne = Department::factory()->create(['name' => 'Dept One '.Str::random(4)]);
    $departmentTwo = Department::factory()->create(['name' => 'Dept Two '.Str::random(4)]);

    $institutionDepartmentOne = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $departmentOne->id,
        'department_code' => 'DEPT-ONE-'.Str::lower(Str::random(6)),
        'description' => 'First department for staff update test',
    ]);

    $institutionDepartmentTwo = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $departmentTwo->id,
        'department_code' => 'DEPT-TWO-'.Str::lower(Str::random(6)),
        'description' => 'Second department for staff update test',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Jimmy',
        'last_name' => 'Ned',
        'email' => 'jimmy.ned.'.Str::random(6).'@example.test',
        'phone_number' => '+26377'.random_int(1000000, 9999999),
    ]);
    $user->assignRole([$roleA, $roleB]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => 'EC-UPD-'.Str::upper(Str::random(6)),
        'date_of_birth' => '1990-01-15',
    ]);

    $staff->institutionDepartments()->sync([
        $institutionDepartmentOne->id,
        $institutionDepartmentTwo->id,
    ]);

    return compact(
        'user',
        'staff',
        'roleA',
        'roleB',
        'roleC',
        'institutionDepartmentOne',
        'institutionDepartmentTwo',
        'title',
        'gender',
        'maritalStatus',
        'employmentType',
    );
}

test('update staff user syncs roles and departments on save', function () {
    $context = makeStaffUserUpdateContext();

    $admin = User::factory()->create(['tenant_id' => Tenant::query()->firstOrFail()->id]);
    $this->actingAs($admin);

    $payload = [
        'first_name' => $context['user']->first_name,
        'middle_name' => $context['user']->middle_name,
        'last_name' => $context['user']->last_name,
        'email' => $context['user']->email,
        'phone_number' => $context['user']->phone_number,
        'employee_number' => $context['staff']->employee_number,
        'date_of_birth' => $context['staff']->date_of_birth,
        'title_id' => $context['title']->id,
        'gender_id' => $context['gender']->id,
        'marital_status_id' => $context['maritalStatus']->id,
        'employment_type_id' => $context['employmentType']->id,
        'role_ids' => [$context['roleB']->id, $context['roleC']->id],
        'department_ids' => [$context['institutionDepartmentTwo']->id],
    ];

    $this->put(route('users.update-staff-user', $context['user']), $payload)
        ->assertRedirect(route('users.show', ['user' => $context['user']->id]));

    $context['user']->refresh();
    $context['staff']->refresh()->load('institutionDepartments');

    expect($context['user']->hasRole($context['roleA']->name))->toBeFalse()
        ->and($context['user']->hasRole($context['roleB']->name))->toBeTrue()
        ->and($context['user']->hasRole($context['roleC']->name))->toBeTrue()
        ->and($context['user']->roles)->toHaveCount(2)
        ->and($context['staff']->institutionDepartments->pluck('id')->all())
        ->toBe([$context['institutionDepartmentTwo']->id]);
});
