<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Enums\Acl\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Institution\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicStaffController extends Controller
{
    /** @return array<int, string> */
    private static function academicStaffRoleSlugs(): array
    {
        return [
            RoleEnum::LECTURER->value,
            RoleEnum::SENIOR_LECTURER->value,
            RoleEnum::LECTURER_IN_CHARGE->value,
            RoleEnum::HEAD_OF_DEPARTMENT->value,
        ];
    }

    public function groupedByDepartment(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $staffMembers = Staff::query()
            ->whereNull('deleted_at')
            ->whereHas('institutionDepartments')
            ->whereHas('user.roles', function ($query): void {
                $query->whereIn('slug', self::academicStaffRoleSlugs());
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where(function ($nameQuery) use ($search): void {
                        $nameQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->with(['user', 'institutionDepartments.department'])
            ->orderBy('id')
            ->get();

        /** @var array<int, array{departmentId: int, departmentName: string, staff: array<int, array{id: int, name: string}>}> $grouped */
        $grouped = [];

        foreach ($staffMembers as $staff) {
            $staffName = trim(sprintf(
                '%s %s',
                (string) ($staff->user?->first_name ?? ''),
                (string) ($staff->user?->last_name ?? ''),
            ));

            foreach ($staff->institutionDepartments as $institutionDepartment) {
                $departmentId = (int) $institutionDepartment->id;

                if (! isset($grouped[$departmentId])) {
                    $grouped[$departmentId] = [
                        'departmentId' => $departmentId,
                        'departmentName' => (string) ($institutionDepartment->department?->name ?? $institutionDepartment->department?->code ?? ''),
                        'staff' => [],
                    ];
                }

                $existingStaffIds = array_column($grouped[$departmentId]['staff'], 'id');

                if (! in_array((int) $staff->id, $existingStaffIds, true)) {
                    $grouped[$departmentId]['staff'][] = [
                        'id' => (int) $staff->id,
                        'name' => $staffName !== '' ? $staffName : (string) ($staff->user?->email ?? ''),
                    ];
                }
            }
        }

        $data = collect($grouped)
            ->sortBy('departmentName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $group): array {
                usort($group['staff'], fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

                return $group;
            })
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }
}
