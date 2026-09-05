<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModeOfStudyController extends Controller
{
    public function __construct(protected IModeOfStudyRepository $repository) {}

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return ModeOfStudyResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function courseModes(DepartmentCourse $departmentCourse, DepartmentLevel $departmentLevel): AnonymousResourceCollection
    {
        $modeIds = CourseLevelMode::query()
            ->where('department_course_id', $departmentCourse->id)
            ->where('department_level_id', $departmentLevel->id)
            ->get()
            ->pluck('modes')
            ->flatten()
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $modes = $modeIds === []
            ? collect()
            : ModeOfStudy::query()->whereIn('id', $modeIds)->orderBy('name')->get();

        return ModeOfStudyResource::collection($modes);
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
