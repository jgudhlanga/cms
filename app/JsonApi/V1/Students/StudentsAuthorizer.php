<?php

namespace App\JsonApi\V1\Students;

use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class StudentsAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool
    {
        $user = $request->user();

        if ($user === null || $modelClass !== StudentProgram::class) {
            return false;
        }

        $studentId = $request->input('filter.student');

        if ($studentId === null || $studentId === '') {
            return false;
        }

        $student = Student::query()->find($studentId);

        if ($student === null) {
            return false;
        }

        if ($this->canViewOwnStudentPrograms($user, $student)) {
            return true;
        }

        if (! $this->hasStaffStudentProgramAccess($user)) {
            return false;
        }

        return $user->can('viewAny:students')
            || $user->can('view:students')
            || $user->can('view', $student);
    }

    public function store(Request $request, string $modelClass): bool
    {
        return false;
    }

    public function show(Request $request, object $model): bool
    {
        $user = $request->user();

        if ($user === null || ! $model instanceof StudentProgram) {
            return false;
        }

        $student = Student::query()->find($model->student_id);

        if ($student !== null && $this->canViewOwnStudentPrograms($user, $student)) {
            return true;
        }

        return $this->hasStaffStudentProgramAccess($user);
    }

    public function update(Request $request, object $model): bool
    {
        return false;
    }

    public function destroy(Request $request, object $model): bool
    {
        return false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    private function hasStaffStudentProgramAccess(User $user): bool
    {
        return $user->can('viewAny:student-programs')
            || $user->can('view:student-programs')
            || $user->can('root:manage')
            || $user->can('viewOnlyOwnDepartment:departments');
    }

    private function canViewOwnStudentPrograms(User $user, Student $student): bool
    {
        if ($user->studentProfile?->id !== $student->id) {
            return false;
        }

        return $user->can('manageOwnStudentProgramDetails:students')
            || $user->can('manageOwnStudentPersonalDetails:students');
    }
}
