<?php

namespace Database\Seeders\Institution;

use App\Enums\TenantEnum;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstitutionDepartmentsTableSeeder extends Seeder
{

    public function run(): void
    {
        # Tenant
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        # departments
        $departmentIds = Department::pluck('id')->toArray();
        foreach ($departmentIds as $departmentId) {
            InstitutionDepartment::create([
                'department_id' => $departmentId,
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
