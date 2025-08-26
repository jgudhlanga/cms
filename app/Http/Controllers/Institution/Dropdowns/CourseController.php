<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\CourseDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\CourseRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\CourseResource;
use App\Models\Institution\Course;
use App\Repositories\Institution\interface\ICourseRepository;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function __construct(protected ICourseRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $courses = CourseResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/dropdowns/courses/Index', [
            'courses' => $courses,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(CourseRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(CourseDto::fromCourseRequest($request));
    }

    public function show(Course $course)
    {
        //
    }

    public function edit(Course $course)
    {
        //
    }

    public function update(CourseRequest $request, Course $course)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->update($course, CourseDto::fromCourseRequest($request));
    }

    public function movePosition(PositionRequest $request, Course $course)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->movePosition($course, $request);
    }

    public function destroy(Course $course)
    {
        $this->authorize('deleteInstitutionSettings');
        $this->repository->delete($course);
    }

    public function restore(string $id)
    {
        $course = $this->repository->findTrashed($id);
        $this->authorize('restoreInstitutionSettings');
        $this->repository->restore($course);
    }

    public function forceDelete(Course $course)
    {
        $this->authorize('forceDeleteInstitutionSettings');
        $this->repository->delete($course, true);
    }
}
