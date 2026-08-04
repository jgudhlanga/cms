<?php

use App\Enums\Rbac\RoleGroupEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Enums\Shared\StatusEnum;
use App\Helpers\PermissionHelper;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Rbac\Role;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Services\Maintenance\Staff\StaffExportService;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__.'/MaintenanceControllerTest.php';

/**
 * @return array{
 *     actor: User,
 *     staffUser: User,
 *     staff: Staff,
 *     role: Role,
 *     departmentName: string,
 *     employeeNumber: string,
 * }
 */
function makeStaffExportContext(): array
{
    $actor = actingAsRootMaintenanceUser();
    $tenantId = (int) $actor->tenant_id;

    test()->seed(RoleGroupSeeder::class);

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $employmentType = EmploymentType::query()->firstOrCreate([
        'name' => EmploymentTypeEnum::FULL_TIME->value,
    ], [
        'description' => EmploymentTypeEnum::FULL_TIME->description(),
    ]);

    $role = Role::factory()->create([
        'name' => 'Export Lecturer',
        'slug' => 'export-lecturer-'.Str::lower(Str::random(6)),
        'guard_name' => 'web',
        'role_group_id' => PermissionHelper::getGroupId(RoleGroupEnum::ACADEMIC->value),
    ]);

    $departmentName = 'Export Engineering '.Str::random(4);
    $department = Department::factory()->create(['name' => $departmentName]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $department->id,
        'department_code' => 'EXP-ENG-'.Str::lower(Str::random(6)),
        'description' => 'Staff export test department',
    ]);

    $employeeNumber = 'EC-EXP-'.Str::upper(Str::random(6));

    $staffUser = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Alice',
        'middle_name' => null,
        'last_name' => 'Exporter',
        'email' => 'alice.exporter.'.Str::random(6).'@example.test',
        'phone_number' => '+263771112233',
        'status_id' => StatusEnum::ACTIVE->id(),
    ]);
    $staffUser->assignRole($role);

    $staff = Staff::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $staffUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => $employeeNumber,
        'id_number' => '63-123456-A-12',
        'passport_number' => null,
    ]);

    $staff->institutionDepartments()->sync([$institutionDepartment->id]);

    return [
        'actor' => $actor,
        'staffUser' => $staffUser,
        'staff' => $staff,
        'role' => $role,
        'departmentName' => $departmentName,
        'employeeNumber' => $employeeNumber,
    ];
}

it('redirects guests from staff export endpoint', function (): void {
    $this->get(route('maintenance.staff.export'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from staff export endpoint', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('maintenance.staff.export'))
        ->assertForbidden();
});

it('exports non-deleted staff as an excel spreadsheet', function (): void {
    $context = makeStaffExportContext();

    $deletedUser = User::factory()->create([
        'tenant_id' => $context['actor']->tenant_id,
        'first_name' => 'Deleted',
        'last_name' => 'Staffer',
        'email' => 'deleted.staffer.'.Str::random(6).'@example.test',
    ]);

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    $employmentType = EmploymentType::query()->firstOrCreate([
        'name' => EmploymentTypeEnum::FULL_TIME->value,
    ], [
        'description' => EmploymentTypeEnum::FULL_TIME->description(),
    ]);

    $deletedStaff = Staff::query()->create([
        'tenant_id' => $context['actor']->tenant_id,
        'user_id' => $deletedUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => 'EC-DEL-'.Str::upper(Str::random(6)),
    ]);
    $deletedStaff->delete();

    $response = $this->get(route('maintenance.staff.export'));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
    $response->assertDownload();

    $spreadsheet = IOFactory::load($response->getFile()->getPathname());
    $rows = $spreadsheet->getActiveSheet()->toArray();

    expect($rows[0])->toBe(StaffExportService::HEADERS);

    $flat = collect($rows)->flatten()->filter()->implode(' ');

    expect($flat)
        ->toContain('Alice Exporter')
        ->toContain($context['employeeNumber'])
        ->toContain($context['staffUser']->email)
        ->toContain('+263771112233')
        ->toContain('Export Lecturer')
        ->toContain($context['departmentName'])
        ->toContain(EmploymentTypeEnum::FULL_TIME->value)
        ->toContain('Male')
        ->toContain('63-123456-A-12')
        ->toContain(StatusEnum::ACTIVE->value)
        ->toContain('Mr')
        ->and($flat)->not->toContain('Deleted Staffer')
        ->and($flat)->not->toContain($deletedStaff->employee_number);
});
