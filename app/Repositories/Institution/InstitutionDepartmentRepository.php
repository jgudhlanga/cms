<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\InstitutionDepartmentDto;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;

class InstitutionDepartmentRepository extends BaseRepository implements IInstitutionDepartmentRepository
{
    public function __construct(protected InstitutionDepartment $institutionDepartment)
    {
        parent::__construct($this->institutionDepartment);
    }


    public function allFilter($columns = ['*'], InstitutionDepartmentFilter $filters = null)
    {
        return $this->institutionDepartment
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    public function syncInstitutionDepartment(InstitutionDepartmentDto $dto): void
    {
        $newIds = $dto->department_ids;

        // Fetch all current and soft-deleted department links
        $allInstitutionDepartments = $this->institutionDepartment->withTrashed()
            ->whereHas('department', function ($query) use ($dto) {
                $query->where('is_academic', $dto->is_academic);
            })->get();
        // Build a map of department_id => model
        $allByDepartmentId = $allInstitutionDepartments->keyBy('department_id');

        // Extract current department IDs (including soft-deleted)
        $existing = $allByDepartmentId->keys()->toArray();

        $toRemove = array_diff($existing, $newIds);

        // Remove unlinked departments (soft delete)
        if (!empty($toRemove)) {
            $this->institutionDepartment
                ->whereIn('department_id', $toRemove)
                ->delete();
        }

        // Handle additions/restorations
        foreach ($newIds as $departmentId) {
            $existingLink = $allByDepartmentId->get($departmentId);

            if ($existingLink) {
                if ($existingLink->trashed()) {
                    $existingLink->restore();
                }
                // Already active, nothing to do
            } else {
                $this->institutionDepartment->create([
                    'department_id' => $departmentId,
                ]);
            }
        }
    }

}
