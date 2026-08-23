<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\Department;
use App\Models\Institution\Division;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Rbac\Permission;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Support\Institution\DepartmentColorPalette;
use Database\Seeders\Rbac\PermissionsTableSeeder;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new PermissionsTableSeeder)->run();
    (new RolesTableSeeder)->run();
});

function makeInstitutionDepartmentsViewer(): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    Permission::findOrCreate('view:department-metadata', 'web');
    Permission::findOrCreate('update:department-metadata', 'web');
    $user->givePermissionTo(['view:department-metadata', 'update:department-metadata']);

    return $user;
}

function makeDepartmentStaff(User $user, InstitutionDepartment $department, RoleEnum $role): Staff
{
    $user->assignRole($role->name());

    $staff = Staff::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'employee_number' => 'DEPT-'.uniqid(),
    ]);

    $staff->institutionDepartments()->syncWithoutDetaching([$department->id]);

    return $staff;
}

test('institution departments index returns color and expanded metadata', function () {
    $user = makeInstitutionDepartmentsViewer();
    $seeded = seedGuestRegistrationProgramme();

    $institutionDepartment = InstitutionDepartment::query()->findOrFail($seeded['departmentId']);
    $institutionDepartment->department()->update(['is_academic' => true]);
    $institutionDepartment->update([
        'color_code' => '#123456',
        'has_apprentice_courses' => true,
    ]);

    $divisionHeadUser = User::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Division',
        'middle_name' => null,
        'last_name' => 'Head',
    ]);
    $divisionHeadStaff = makeDepartmentStaff($divisionHeadUser, $institutionDepartment, RoleEnum::HEAD_OF_DIVISION);

    $division = Division::query()->create([
        'name' => 'Applied Sciences Division',
        'head_of_division_id' => $divisionHeadStaff->id,
    ]);
    $institutionDepartment->update(['division_id' => $division->id]);

    $hodUser = User::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Applied',
        'middle_name' => null,
        'last_name' => 'HOD',
    ]);
    makeDepartmentStaff($hodUser, $institutionDepartment, RoleEnum::HEAD_OF_DEPARTMENT);

    $response = $this->actingAs($user)
        ->get(route('institution-departments.index', ['is_academic' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('institution/departments/Index'));

    $rows = collect($response->original->getData()['page']['props']['departments']['data']);
    $row = $rows->firstWhere('id', $institutionDepartment->id);

    expect($row)->not->toBeNull()
        ->and($row['attributes']['colorCode'])->toBe('#123456')
        ->and($row['attributes']['headOfDepartment'])->toBe('Applied HOD')
        ->and($row['attributes']['headOfDivision'])->toBe('Division Head')
        ->and($row['attributes']['division'])->toBe('Applied Sciences Division')
        ->and($row['attributes']['coursesOfferedCount'])->toBe(1)
        ->and($row['attributes']['hasApprenticeCourses'])->toBeTrue();
});

test('institution department update persists color code', function () {
    $user = makeInstitutionDepartmentsViewer();
    $seeded = seedGuestRegistrationProgramme();
    $institutionDepartment = InstitutionDepartment::query()->findOrFail($seeded['departmentId']);
    $institutionDepartment->update(['color_code' => '#111111']);

    $this->actingAs($user)
        ->from(route('institution-departments.index', ['is_academic' => 1]))
        ->put(route('institution-departments.update', $institutionDepartment->id), [
            'division_id' => null,
            'color_code' => '#ABCDEF',
            'has_apprentice_courses' => false,
        ])
        ->assertSuccessful();

    expect($institutionDepartment->fresh()->color_code)->toBe('#ABCDEF');
});

test('syncing a new institution department assigns a palette color', function () {
    $user = makeInstitutionDepartmentsViewer();
    Permission::findOrCreate('create:department-metadata', 'web');
    $user->givePermissionTo('create:department-metadata');

    $department = Department::query()->create([
        'name' => 'Palette Test '.uniqid(),
        'is_academic' => true,
    ]);

    $this->actingAs($user)
        ->post(route('institution-departments.sync'), [
            'is_academic' => true,
            'department_ids' => [$department->id],
        ])
        ->assertSuccessful();

    $linked = InstitutionDepartment::query()
        ->where('department_id', $department->id)
        ->first();

    expect($linked)->not->toBeNull()
        ->and(DepartmentColorPalette::isValid($linked->color_code))->toBeTrue();
});

test('institution department update rejects duplicate color codes within tenant', function () {
    $user = makeInstitutionDepartmentsViewer();
    $seeded = seedGuestRegistrationProgramme();

    $first = InstitutionDepartment::query()->findOrFail($seeded['departmentId']);
    $first->update(['color_code' => '#FF0000']);

    $secondDepartment = Department::query()->create([
        'name' => 'Color Collision '.uniqid(),
        'is_academic' => true,
    ]);

    $second = InstitutionDepartment::query()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => $secondDepartment->id,
        'color_code' => '#00FF00',
    ]);

    $this->actingAs($user)
        ->from(route('institution-departments.index', ['is_academic' => 1]))
        ->put(route('institution-departments.update', $second->id), [
            'division_id' => null,
            'color_code' => '#ff0000',
            'has_apprentice_courses' => false,
        ])
        ->assertSessionHasErrors('color_code');

    expect($second->fresh()->color_code)->toBe('#00FF00');
});
