<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Staff;

use App\Models\Institution\Staff;
use Illuminate\Support\Collection;

class StaffExportService
{
    /**
     * @var list<string>
     */
    public const HEADERS = [
        'Title',
        'Name',
        'EC Number',
        'Email',
        'Phone',
        'Roles',
        'Department',
        'Employment type',
        'Gender',
        'National ID',
        'Account status',
    ];

    /**
     * @return list<list<string|null>>
     */
    public function rows(): array
    {
        $rows = [self::HEADERS];
        $exportedIds = [];

        Staff::query()
            ->with([
                'user.status',
                'user.roles',
                'title',
                'gender',
                'employmentType',
                'institutionDepartments.department',
            ])
            ->orderBy('id')
            ->chunkById(200, function (Collection $staffMembers) use (&$rows, &$exportedIds): void {
                foreach ($staffMembers as $staff) {
                    /** @var Staff $staff */
                    $id = (int) $staff->id;
                    if (isset($exportedIds[$id])) {
                        continue;
                    }
                    $exportedIds[$id] = true;
                    $rows[] = $this->mapRow($staff);
                }
            });

        return $rows;
    }

    public function downloadFileName(): string
    {
        return 'staff-export-'.now()->format('Y-m-d_His').'.xlsx';
    }

    /**
     * @return list<string|null>
     */
    private function mapRow(Staff $staff): array
    {
        $user = $staff->user;

        $roles = $user?->roles
            ?->pluck('name')
            ->filter()
            ->sort()
            ->values()
            ->implode(', ');

        $departments = $staff->institutionDepartments
            ->map(fn ($institutionDepartment) => $institutionDepartment->department?->name)
            ->filter()
            ->sort()
            ->values()
            ->implode(', ');

        $nationalId = $staff->id_number ?: $staff->passport_number;

        return [
            $staff->title?->name,
            $user?->full_name,
            $staff->employee_number,
            $user?->email,
            $user?->phone_number,
            $roles !== '' ? $roles : null,
            $departments !== '' ? $departments : null,
            $staff->employmentType?->name,
            $staff->gender?->title,
            $nationalId,
            $user?->status?->title,
        ];
    }
}
