<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Students\StudentFilter;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Institution\CourseResource;
use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Students\Student;
use App\Models\Users\User;
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
        return Inertia::render('students/StudentsIndex', [
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

    public function searchProfile()
    {
        [$search] = $this->extractRequestFilters();

        if (blank($search)) {
            return response()->json([
                'message' => 'Please provide a search value.',
            ], 422);
        }

        // 1. Search by user email
        $user = User::where('email', $search)->first();

        // 2. Search students by id_number, student_number or passport_number
        if (!$user) {
            $student = Student::query()
                ->where('id_number', $search)
                ->orWhere('student_number', $search)
                ->orWhere('passport_number', $search)
                ->first();
            $user = $student?->user;
        }


        if ($user) {
            return UserResource::make($user);
        }

        return response()->json([
            'message' => 'No matching record found.',
        ], 200);
    }


    private function extractRequestFilters(): array
    {
        $search = request()->has('search') ? request('search') : null;

        return [
            $search,
        ];
    }
}
