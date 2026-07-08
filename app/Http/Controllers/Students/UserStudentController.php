<?php

namespace App\Http\Controllers\Students;

use App\Helpers\EnrolmentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentApplicationRequest;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
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
    public function updateProgram(StudentApplication $studentApplication, UpdateStudentApplicationRequest $request)
    {
        $this->authorize('update', $studentApplication);
        $validated = $request->validated();

        DB::transaction(function () use ($studentApplication, $validated) {
            $oldInstitutionDepartmentId = $studentApplication->institution_department_id;
            $studentApplication->update($validated);

            $studentEnrolment = StudentEnrolment::query()
                ->where('student_application_id', $studentApplication->id)
                ->first();

            $studentEnrolment?->update([
                'institution_department_id' => $validated['institution_department_id'],
                'department_level_id' => $validated['department_level_id'],
                'department_course_id' => $validated['department_course_id'],
                'mode_of_study_id' => $validated['mode_of_study_id'],
            ]);

            if ($oldInstitutionDepartmentId != $validated['institution_department_id'] && $studentApplication->student->student_number_generated) {
                $studentNumber = EnrolmentHelper::resolveStudentNumber($studentApplication);
                $studentApplication->student->update([
                    'student_number' => $studentNumber,
                ]);
            }
        });
    }
}
