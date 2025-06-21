<?php

namespace App\Http\Controllers\Api\V1\Religions;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Religions\ReligionResource;
use App\Repositories\Religions\interface\IReligionRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class ReligionController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IReligionRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return ReligionResource::collection($this->repository->allFilter(['*'], $filters));
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
