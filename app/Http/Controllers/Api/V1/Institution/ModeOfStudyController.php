<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\DepartmentCourse;
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
    public function courseModes(DepartmentCourse $departmentCourse): AnonymousResourceCollection
    {
        $modes = ModeOfStudy::join('course_modes', 'mode_of_studies.id', '=', 'course_modes.mode_of_study_id')
            ->where('course_modes.department_course_id', $departmentCourse->id)
            ->select('mode_of_studies.*')
            ->get();
        return ModeOfStudyResource::collection($modes);
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
