<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Students\StudentFilter;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Institution\CourseResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Students\Student;
use App\Repositories\Students\interface\IStudentRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        protected IStudentRepository $repository,
    )
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(StudentFilter $filters): Response
    {
        $this->authorize('viewAny', Student::class);
        $students = StudentResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('students/Index', [
            'students' => $students,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show(string $id)
    {
        //
    }


    public function edit(string $id)
    {
        //
    }


    public function update(UpdateStudentRequest $request, Student $student): void
    {
        $this->repository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    public function destroy(string $id)
    {
        //
    }
}
