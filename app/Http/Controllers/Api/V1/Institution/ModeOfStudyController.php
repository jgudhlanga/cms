<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModeOfStudyController extends Controller
{
    public function __construct(protected IModeOfStudyRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return ModeOfStudyResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function courseModes(DepartmentCourse $departmentCourse, DepartmentLevel $departmentLevel): AnonymousResourceCollection
    {
        $courseLevelModes = $departmentCourse->courseLevelModes()->where('department_level_id', $departmentLevel->id)->get(); // Eager-loaded or lazy-loaded
        $modeObjects = $courseLevelModes->flatMap(fn($clm) => $clm->mode_objects);
        return ModeOfStudyResource::collection($modeObjects);
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
