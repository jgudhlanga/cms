<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Staff;

use App\Enums\Rbac\RoleGroupEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Role;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StaffImportLookupCreator
{
    /**
     * @return array{value: int, label: string}
     */
    public function create(int $tenantId, string $type, string $name): array
    {
        $normalizedName = trim($name);

        return match ($type) {
            'title' => $this->createTitle($normalizedName),
            'gender' => $this->createGender($normalizedName),
            'marital_status' => $this->createMaritalStatus($normalizedName),
            'employment_type' => $this->createEmploymentType($normalizedName),
            'department' => $this->createDepartment($tenantId, $normalizedName),
            'role' => $this->createRole($normalizedName),
            default => throw new InvalidArgumentException("Unsupported lookup type [{$type}]."),
        };
    }

    /**
     * @return array{value: int, label: string}
     */
    private function createTitle(string $name): array
    {
        $title = Title::query()->firstOrCreate(['name' => $name]);

        return [
            'value' => $title->id,
            'label' => (string) $title->name,
        ];
    }

    /**
     * @return array{value: int, label: string}
     */
    private function createGender(string $name): array
    {
        $gender = Gender::query()->firstOrCreate(['title' => $name]);

        return [
            'value' => $gender->id,
            'label' => (string) $gender->title,
        ];
    }

    /**
     * @return array{value: int, label: string}
     */
    private function createMaritalStatus(string $name): array
    {
        $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => $name]);

        return [
            'value' => $maritalStatus->id,
            'label' => (string) $maritalStatus->title,
        ];
    }

    /**
     * @return array{value: int, label: string}
     */
    private function createEmploymentType(string $name): array
    {
        $employmentType = EmploymentType::query()->firstOrCreate(['name' => $name]);

        return [
            'value' => $employmentType->id,
            'label' => (string) $employmentType->name,
        ];
    }

    /**
     * @return array{value: int, label: string}
     */
    private function createDepartment(int $tenantId, string $name): array
    {
        $department = Department::query()->firstOrCreate(
            ['name' => $name],
            [
                'description' => $name,
                'is_academic' => true,
            ],
        );

        $institutionDepartment = InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->where('department_id', $department->id)
            ->first();

        if ($institutionDepartment === null) {
            $institutionDepartment = InstitutionDepartment::query()->create([
                'tenant_id' => $tenantId,
                'department_id' => $department->id,
                'department_code' => $this->generateDepartmentCode($name),
                'description' => $name,
            ]);
        }

        return [
            'value' => $institutionDepartment->id,
            'label' => $name,
        ];
    }

    /**
     * @return array{value: int, label: string}
     */
    private function createRole(string $name): array
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            [
                'role_group_id' => PermissionHelper::getGroupId(RoleGroupEnum::ACADEMIC->value),
                'description' => $name,
            ],
        );

        return [
            'value' => $role->id,
            'label' => (string) $role->name,
        ];
    }

    private function generateDepartmentCode(string $name): string
    {
        $base = strtoupper(Str::slug(Str::limit($name, 12, ''), '_'));

        if ($base === '') {
            $base = 'DEPT';
        }

        return $base.'-'.strtoupper(Str::random(4));
    }
}
