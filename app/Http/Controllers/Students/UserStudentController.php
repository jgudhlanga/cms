<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Actions\Students\ReassignStudentProgrammeAction;
use App\DTO\Students\ReassignStudentProgrammeDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentApplicationRequest;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Throwable;

class UserStudentController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(User $user)
    {
        $this->authorize('viewAny', Student::class);

        $student = $user->studentProfile;

        if ($student === null) {
            abort(404);
        }

        return redirect()->route('students.show', $student);
    }

    /**
     * @throws AuthorizationException
     */
    public function edit(StudentApplication $studentApplication)
    {
        $this->authorize('update', $studentApplication);

        $student = $studentApplication->student;
        $user = $student->user;

        return Inertia::render('students/EditStudentApplication', [
            'user' => UserResource::make($user),
            'student' => $user?->studentProfile ? StudentResource::make($user->studentProfile) : null,
            'program' => EnrolmentResource::make($studentApplication),
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function updateProgram(
        StudentApplication $studentApplication,
        UpdateStudentApplicationRequest $request,
        ReassignStudentProgrammeAction $reassign,
    ): void {
        $this->authorize('update', $studentApplication);

        $reassign->execute(
            $studentApplication,
            ReassignStudentProgrammeDto::fromArray($request->validated()),
        );
    }
}
