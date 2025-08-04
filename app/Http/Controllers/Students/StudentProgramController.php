<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Students\StudentProgramFilter;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Students\StudentProgramResource;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Repositories\Students\interface\IStudentProgramRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentProgramController extends Controller
{
    public function __construct(
        protected IStudentProgramRepository $repository,
    )
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(StudentProgramFilter $filters): Response
    {
        $this->authorize('viewAny', StudentProgram::class);
        $enrolments = StudentProgramResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('students/EnrolmentsIndex', [
            'enrolments' => $enrolments,
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
