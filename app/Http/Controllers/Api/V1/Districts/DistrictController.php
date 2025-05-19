<?php

namespace App\Http\Controllers\Api\V1\Districts;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Districts\DistrictResource;
use App\Repositories\Districts\interface\IDistrictRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class DistrictController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected IDistrictRepository $repository)
	{

	}

    public function index(SharedNameFilter $filters)
    {
        return DistrictResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
