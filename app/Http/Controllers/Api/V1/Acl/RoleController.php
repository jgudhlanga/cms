<?php

namespace App\Http\Controllers\Api\V1\Acl;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Acl\RoleFilter;
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

    public function index(RoleFilter $filters)
    {
        return RoleResource::collection($this->repository->allFilter(['*'], $filters))->additional([
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
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
