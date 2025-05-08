<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\DepartmentResource;
use App\Repositories\Institution\interface\IDepartmentRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class DepartmentController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IDepartmentRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return DepartmentResource::collection($this->repository->allFilter(['*'], $filters));
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
