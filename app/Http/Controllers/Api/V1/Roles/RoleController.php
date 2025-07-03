<?php

namespace App\Http\Controllers\Api\V1\Roles;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Acl\PermissionFilter;
use App\Http\Resources\Acl\RoleResource;
use App\Repositories\Acl\Interface\IRoleRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class RoleController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IRoleRepository $repository)
    {

    }

    public function index(PermissionFilter $filters)
    {
        return RoleResource::collection($this->repository->allFilter(['*'], $filters));
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
