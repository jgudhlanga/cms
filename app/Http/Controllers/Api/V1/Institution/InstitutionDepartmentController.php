<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class InstitutionDepartmentController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IInstitutionDepartmentRepository $repository)
    {
    }

    public function index(InstitutionDepartmentFilter $filters)
    {
        // select only departments which are in department_courses or department_levels
        $departments = InstitutionDepartment::query()
            ->with('department')
            ->join(
                'department_courses as dc',
                'dc.institution_department_id',
                '=',
                'institution_departments.id'
            )
            ->where('dc.show_on_current_application_period', true)
            ->select('institution_departments.*')
            ->distinct()
            ->orderBy('institution_departments.created_at')
            ->orderBy('institution_departments.deleted_at')
            ->paginate();
        // return InstitutionDepartmentResource::collection($this->repository->allFilter(['*'], $filters));
        return InstitutionDepartmentResource::collection($departments);
    }

    public function store(Request $request)
    {
    }

    public function show(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }
}
