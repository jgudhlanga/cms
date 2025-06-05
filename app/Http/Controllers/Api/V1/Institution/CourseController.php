<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\CourseResource;
use App\Repositories\Institution\interface\ICourseRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class CourseController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected ICourseRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return CourseResource::collection($this->repository->allFilter(['*'], $filters));
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
