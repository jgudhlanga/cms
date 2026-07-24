<?php

namespace App\Http\Controllers\Api\V1\Rbac;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Rbac\RoleGroupResource;
use App\Repositories\Rbac\Interface\IRoleGroupRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class RoleGroupController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IRoleGroupRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return RoleGroupResource::collection($this->repository->allFilter(['*'], $filters));
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
