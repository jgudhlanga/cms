<?php

namespace App\Http\Controllers\Api\V1\Provinces;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Resources\Provinces\ProvinceResource;
use App\Repositories\Provinces\interface\IProvinceRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class ProvinceController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected IProvinceRepository $repository)
	{

	}

    public function index(SharedTitleFilter $filters)
    {
        return ProvinceResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
