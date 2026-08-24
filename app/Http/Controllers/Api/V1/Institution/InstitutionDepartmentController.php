<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class InstitutionDepartmentController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IInstitutionDepartmentRepository $repository) {}

    public function index(InstitutionDepartmentFilter $filters)
    {
        $departmentIds = ApplicationOfferingDepartment::query()->pluck('institution_department_id');

        $departments = InstitutionDepartment::query()
            ->with('department')
            ->whereIn('institution_departments.id', $departmentIds)
            ->select('institution_departments.*')
            ->distinct()
            ->orderBy('institution_departments.created_at')
            ->orderBy('institution_departments.deleted_at')
            ->paginate();

        return InstitutionDepartmentResource::collection($departments);
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
