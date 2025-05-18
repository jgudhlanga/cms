<?php

namespace App\Http\Controllers\Api\V1\Titles;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Titles\TitleResource;
use App\Repositories\Titles\interface\ITitleRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class TitleController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected ITitleRepository $repository)
	{

	}

    public function index(SharedNameFilter $filters)
    {
        return TitleResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
