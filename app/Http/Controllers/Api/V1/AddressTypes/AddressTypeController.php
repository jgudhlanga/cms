<?php

namespace App\Http\Controllers\Api\V1\AddressTypes;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Resources\AddressTypes\AddressTypeResource;
use App\Repositories\AddressTypes\interface\IAddressTypeRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class AddressTypeController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected IAddressTypeRepository $repository)
	{

	}

    public function index(SharedTitleFilter $filters)
    {
        return AddressTypeResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
