<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Users\UserFilter;
use App\Http\Resources\Users\UserResource;
use App\Repositories\Users\interface\IUserRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class UserController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IUserRepository $repository)
    {

    }

    public function index(UserFilter $filters)
    {
        return UserResource::collection($this->repository->allFilter(['*'], $filters))->additional([
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
