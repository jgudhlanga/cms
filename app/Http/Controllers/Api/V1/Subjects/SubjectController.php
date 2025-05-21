<?php

namespace App\Http\Controllers\Api\V1\Subjects;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\SubjectResource;
use App\Repositories\Institution\interface\ISubjectRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class SubjectController extends ApiDropdownController
{
	use HttpUtil;

	public function __construct(protected ISubjectRepository $repository) {}

    public function index(SharedNameFilter $filters)
    {
        return SubjectResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
