<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Shared\IdTypeResource;
use App\Repositories\Shared\interface\IIdTypeRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class IdTypeController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected IIdTypeRepository $repository)
	{

	}

    public function index(SharedNameFilter $filters)
    {
        return IdTypeResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
