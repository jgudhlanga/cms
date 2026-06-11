<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Staff;

use App\Enums\Acl\RoleGroupEnum;
use App\Helpers\PermissionHelper;
use App\Models\Acl\Role;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use Illuminate\Support\Collection;

class StaffImportLookups
{
    /**
     * @var list<RoleGroupEnum>
     */
    private const IMPORTABLE_ROLE_GROUPS = [
        RoleGroupEnum::ACADEMIC,
        RoleGroupEnum::ADMINISTRATIVE,
        RoleGroupEnum::MANAGERIAL,
        RoleGroupEnum::SERVICE_AND_SUPPORT,
    ];

    /**
     * @return array{
     *     titles: list<array{value: int, label: string}>,
     *     genders: list<array{value: int, label: string}>,
     *     maritalStatuses: list<array{value: int, label: string}>,
     *     employmentTypes: list<array{value: int, label: string}>,
     *     departments: list<array{value: int, label: string}>,
     *     roles: list<array{value: int, label: string, roleGroup: string}>,
     * }
     */
    public function optionsForPreview(int $tenantId): array
    {
        return [
            'titles' => Title::query()->orderBy('name')->get()->map(fn (Title $title): array => [
                'value' => $title->id,
                'label' => (string) $title->name,
            ])->all(),
            'genders' => Gender::query()->orderBy('title')->get()->map(fn (Gender $gender): array => [
                'value' => $gender->id,
                'label' => (string) $gender->title,
            ])->all(),
            'maritalStatuses' => MaritalStatus::query()->orderBy('title')->get()->map(fn (MaritalStatus $status): array => [
                'value' => $status->id,
                'label' => (string) $status->title,
            ])->all(),
            'employmentTypes' => EmploymentType::query()->orderBy('name')->get()->map(fn (EmploymentType $type): array => [
                'value' => $type->id,
                'label' => (string) $type->name,
            ])->all(),
            'departments' => InstitutionDepartment::query()
                ->where('tenant_id', $tenantId)
                ->with('department')
                ->get()
                ->filter(fn (InstitutionDepartment $dept): bool => $dept->department !== null)
                ->sortBy(fn (InstitutionDepartment $dept): string => (string) $dept->department?->name)
                ->map(fn (InstitutionDepartment $dept): array => [
                    'value' => $dept->id,
                    'label' => (string) $dept->department?->name,
                ])
                ->values()
                ->all(),
            'roles' => $this->importableRoles()
                ->map(fn (Role $role): array => [
                    'value' => $role->id,
                    'label' => (string) $role->name,
                    'roleGroup' => (string) $role->roleGroup?->name,
                ])
                ->all(),
        ];
    }

    /**
     * @return array{
     *     titles: list<string>,
     *     genders: list<string>,
     *     maritalStatuses: list<string>,
     *     employmentTypes: list<string>,
     *     departments: list<string>,
     *     roles: list<string>,
     * }
     */
    public function labelsForTemplate(int $tenantId): array
    {
        $options = $this->optionsForPreview($tenantId);

        return [
            'titles' => array_column($options['titles'], 'label'),
            'genders' => array_column($options['genders'], 'label'),
            'maritalStatuses' => array_column($options['maritalStatuses'], 'label'),
            'employmentTypes' => array_column($options['employmentTypes'], 'label'),
            'departments' => array_column($options['departments'], 'label'),
            'roles' => $this->importableRoles()
                ->map(fn (Role $role): string => (string) $role->slug)
                ->all(),
        ];
    }

    /**
     * @return list<array{id: int, label: string, slug?: string|null}>
     */
    public function titleCandidates(): array
    {
        return Title::query()->orderBy('name')->get()->map(fn (Title $title): array => [
            'id' => $title->id,
            'label' => (string) $title->name,
        ])->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function genderCandidates(): array
    {
        return Gender::query()->orderBy('title')->get()->map(fn (Gender $gender): array => [
            'id' => $gender->id,
            'label' => (string) $gender->title,
        ])->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function maritalStatusCandidates(): array
    {
        return MaritalStatus::query()->orderBy('title')->get()->map(fn (MaritalStatus $status): array => [
            'id' => $status->id,
            'label' => (string) $status->title,
        ])->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function employmentTypeCandidates(): array
    {
        return EmploymentType::query()->orderBy('name')->get()->map(fn (EmploymentType $type): array => [
            'id' => $type->id,
            'label' => (string) $type->name,
        ])->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function departmentCandidates(int $tenantId): array
    {
        return InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->with('department')
            ->get()
            ->filter(fn (InstitutionDepartment $dept): bool => $dept->department !== null)
            ->map(fn (InstitutionDepartment $dept): array => [
                'id' => $dept->id,
                'label' => (string) $dept->department?->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, slug: string}>
     */
    public function roleCandidates(): array
    {
        return $this->importableRoles()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'label' => (string) $role->name,
                'slug' => (string) $role->slug,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Role>
     */
    private function importableRoles(): Collection
    {
        $groupIds = array_values(array_filter(array_map(
            fn (RoleGroupEnum $group): ?int => PermissionHelper::getGroupId($group->value),
            self::IMPORTABLE_ROLE_GROUPS,
        )));

        $query = Role::query()
            ->with('roleGroup')
            ->orderBy('role_group_id')
            ->orderBy('name');

        if ($groupIds !== []) {
            $query->whereIn('role_group_id', $groupIds);
        }

        return $query->get();
    }
}
