<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentProgramRequest;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;

class UserStudentController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function index(User $user)
    {
        $this->authorize('viewAny', Student::class);
        $studentModel = $user->studentProfile;
        return Inertia::render('students/UserStudentProfile', [
            'user' => UserResource::make($user),
            'student' => $user?->studentProfile ? StudentResource::make($user->studentProfile) : null,
            'programs' => $studentModel?->programs ? EnrolmentResource::collection($studentModel->programs) : [],
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function edit(StudentProgram $studentProgram)
    {
        $student = $studentProgram->student;
        $user = $student->user;
        $this->authorize('update', $student);
        return Inertia::render('students/EditStudentProgram', [
            'user' => UserResource::make($user),
            'student' => $user?->studentProfile ? StudentResource::make($user->studentProfile) : null,
            'program' =>  EnrolmentResource::make($studentProgram),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function updateProgram(StudentProgram $studentProgram, UpdateStudentProgramRequest $request)
    {
        $this->authorize('update', $studentProgram->student);
        $studentProgram->update($request->validated());
    }
}
