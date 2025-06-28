<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Shared\CountryResource;
use App\Repositories\Shared\interface\ICountryRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class CountryController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected ICountryRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return CountryResource::collection($this->repository->allFilter(['*'], $filters));
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
