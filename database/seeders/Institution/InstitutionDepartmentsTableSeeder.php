<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\InstitutionDepartmentEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Seeder;

class InstitutionDepartmentsTableSeeder extends Seeder
{

    public function run(): void
    {
        # Tenant
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        # departments
        foreach (InstitutionDepartmentEnum::cases() as $case) {
            // get the department id from the enum
            $department = Department::where('name', $case->value)->first();
            InstitutionDepartment::create([
                'department_id' => $department->id,
                'tenant_id' => $tenant->id,
                'department_code' => $case->departmentCode(),
            ]);
        }
    }
}
