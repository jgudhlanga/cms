<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\InstitutionDepartmentDto;
use App\Helpers\Helper;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use App\Support\Institution\DepartmentColorPalette;

class InstitutionDepartmentRepository extends BaseRepository implements IInstitutionDepartmentRepository
{
    public function __construct(protected InstitutionDepartment $institutionDepartment)
    {
        parent::__construct($this->institutionDepartment);
    }

    public function allFilter($columns = ['*'], ?InstitutionDepartmentFilter $filters = null)
    {
        $isDepartmentUser = Helper::isDepartmentUser();
        $userDepartments = Helper::resolveUserDepartments();
        if ($isDepartmentUser && empty($userDepartments)) {
            return collect();
        }
        $query = $this->institutionDepartment
            ->with([
                'department',
                'division.headOfDivision.user',
                'departmentLevels.level',
                'staff.user.roles',
            ])
            ->withCount(['departmentCourses', 'staff'])
            ->select($columns)
            ->filter($filters);
        if (! empty($userDepartments)) {
            $query->whereIn('id', $userDepartments);
        }

        return $query->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    public function syncInstitutionDepartment(InstitutionDepartmentDto $dto): void
    {
        $newIds = $dto->department_ids;

        $allInstitutionDepartments = $this->institutionDepartment->withTrashed()
            ->whereHas('department', function ($query) use ($dto) {
                $query->where('is_academic', $dto->is_academic);
            })->get();

        $allByDepartmentId = $allInstitutionDepartments
            ->groupBy('department_id')
            ->map(function ($group) {
                $active = $group->filter(fn ($model) => ! $model->trashed());

                if ($active->isNotEmpty()) {
                    return $active->sortBy('id')->first();
                }

                return $group->sortBy('id')->first();
            });

        $existing = $allByDepartmentId->keys()->toArray();
        $toRemove = array_diff($existing, $newIds);

        if (! empty($toRemove)) {
            $this->institutionDepartment
                ->whereIn('department_id', $toRemove)
                ->whereHas('department', function ($query) use ($dto) {
                    $query->where('is_academic', $dto->is_academic);
                })
                ->delete();
        }

        foreach ($newIds as $departmentId) {
            $existingLink = $allByDepartmentId->get($departmentId);

            if ($existingLink) {
                if ($existingLink->trashed()) {
                    $existingLink->restore();
                }

                continue;
            }

            $anyExisting = $this->institutionDepartment->withTrashed()
                ->where('department_id', $departmentId)
                ->orderBy('id')
                ->first();

            if ($anyExisting) {
                if ($anyExisting->trashed()) {
                    $anyExisting->restore();
                }

                continue;
            }

            $usedColors = $this->institutionDepartment
                ->withTrashed()
                ->whereNotNull('color_code')
                ->pluck('color_code')
                ->map(fn (?string $color): string => strtoupper((string) $color))
                ->all();

            $this->institutionDepartment->create([
                'department_id' => $departmentId,
                'color_code' => DepartmentColorPalette::normalize(
                    DepartmentColorPalette::nextColor($usedColors),
                ),
            ]);
        }
    }
}
