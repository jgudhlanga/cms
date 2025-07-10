<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
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
        return InstitutionDepartmentResource::collection($this->repository->allFilter(['*'], $filters));
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
