<?php

use App\Helpers\Helper;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Tenants\Tenant;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

test('department scope lookups run once per user within a request or job', function () {
    $tenant = Tenant::query()->firstOrFail();
    $department = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => Department::factory()->create(['name' => 'Engineering '.uniqid()])->id,
        'department_code' => 'ENG-'.uniqid(),
        'description' => 'Engineering',
    ]);

    $user = actingAsDepartmentScopedUser($tenant->id, $department);

    $staffDepartmentQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$staffDepartmentQueries): void {
        if (str_contains($query->sql, 'institution_department_staff')) {
            $staffDepartmentQueries++;
        }
    });

    $departmentIds = UserAccessScope::for($user)->departmentIds();
    UserAccessScope::for($user)->departmentIds();
    UserAccessScope::for()->canReachDepartment((int) $department->id);
    Helper::resolveUserDepartments();

    expect($departmentIds)->toBe([(int) $department->id])
        ->and($staffDepartmentQueries)->toBe(1);

    // Queue workers and Octane reset scoped bindings between jobs and requests.
    app()->forgetScopedInstances();
    UserAccessScope::for($user)->departmentIds();

    expect($staffDepartmentQueries)->toBe(2);
});
