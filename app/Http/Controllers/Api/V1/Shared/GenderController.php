<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Resources\Shared\GenderResource;
use App\Repositories\Shared\interface\IGenderRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class GenderController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected IGenderRepository $repository)
	{

	}

    public function index(SharedTitleFilter $filters)
    {
        return GenderResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
