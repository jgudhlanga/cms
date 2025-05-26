<?php

namespace App\Http\Controllers\Api\V1\Statuses;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Resources\Statuses\MaritalStatusResource;
use App\Repositories\Statuses\interface\IMaritalStatusRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class MaritalStatusController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IMaritalStatusRepository $repository)
    {

    }

    public function index(SharedTitleFilter $filters)
    {
        return MaritalStatusResource::collection($this->repository->allFilter(['*'], $filters));
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
