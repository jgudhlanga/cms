<?php

namespace App\Http\Controllers\Api\V1\AcademicCalendars;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\AcademicCalendars\SemesterFilter;
use App\Http\Resources\AcademicCalendars\SemesterResource;
use App\Repositories\AcademicCalendars\interface\ISemesterRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SemesterController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected ISemesterRepository $repository) {}

    public function index(SemesterFilter $filters): AnonymousResourceCollection
    {
        return SemesterResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request): void {}

    public function show(string $id): void {}

    public function update(Request $request, string $id): void {}

    public function destroy(string $id): void {}
}
