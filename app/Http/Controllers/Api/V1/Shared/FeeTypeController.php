<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Shared\FeeTypeResource;
use App\Repositories\Shared\interface\IFeeTypeRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeeTypeController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IFeeTypeRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return FeeTypeResource::collection($this->repository->allFilter(['*'], $filters));
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
