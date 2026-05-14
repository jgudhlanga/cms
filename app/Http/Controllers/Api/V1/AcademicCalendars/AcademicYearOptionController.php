<?php

namespace App\Http\Controllers\Api\V1\AcademicCalendars;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\AcademicCalendars\AcademicYearOptionFilter;
use App\Http\Resources\AcademicCalendars\AcademicYearOptionResource;
use App\Repositories\AcademicCalendars\interface\IAcademicYearOptionRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicYearOptionController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IAcademicYearOptionRepository $repository) {}

    public function index(AcademicYearOptionFilter $filters): AnonymousResourceCollection
    {
        return AcademicYearOptionResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request): void {}

    public function show(string $id): void {}

    public function update(Request $request, string $id): void {}

    public function destroy(string $id): void {}
}
