<?php

namespace App\Http\Controllers\Api\V1\Students;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Students\StudentEnrolmentStatusResource;
use App\Repositories\Students\interface\IStudentEnrolmentStatusRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentEnrolmentStatusController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IStudentEnrolmentStatusRepository $repository) {}

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return StudentEnrolmentStatusResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request): void {}

    public function show(string $id): void {}

    public function update(Request $request, string $id): void {}

    public function destroy(string $id): void {}
}
