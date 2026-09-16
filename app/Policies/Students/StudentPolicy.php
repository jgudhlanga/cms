<?php

namespace App\Policies\Students;

use App\Enums\Shared\ModuleEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;
use App\Support\Rbac\UserAccessScope;

class StudentPolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:students');
    }

    /**
     * Student list and stats API. Department-scoped staff are allowed too: StudentRepository limits
     * their results to their own departments.
     */
    public function viewIndex(User $user): bool
    {
        return $user->can('viewAny:students') || $user->can('viewOnlyOwnDepartment:departments');
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->can('viewAny:students') || $user->can('view:students')) {
            return true;
        }

        if ($user->studentProfile?->id !== $student->id) {
            return false;
        }

        return $user->can('manageOwnStudentApplicationDetails:students')
            || $user->can('manageOwnStudentPersonalDetails:students')
            || $user->can('manageOwnStudentFinancialDetails:students');
    }

    public function create(User $user): bool
    {
        return $user->can('create:students');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('update:students', $student);
    }

    public function uploadIdPhoto(User $user, Student $student): bool
    {
        if ($user->studentProfile?->id === $student->id) {
            return $user->can('manageOwnStudentPersonalDetails:students');
        }

        return $user->can('uploadIdPhoto:students') || $user->can('update:students');
    }

    public function changeStudentNumber(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->can('change-student-number:students');
    }

    public function changeStudentStatus(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->can('change-student-status:students');
    }

    public function changeIntakePeriod(User $user, Student $student): bool
    {
        return $this->view($user, $student) && $user->can('change-intake-period:students');
    }

    /**
     * Record a student's study position for the current period. With an enrolment, the user must
     * also reach that enrolment's department; staff never confirm their own student record.
     */
    public function confirmStudyPosition(User $user, Student $student, ?StudentEnrolment $enrolment = null): bool
    {
        if (! $this->view($user, $student) || ! $user->can('confirm-study-position:students')) {
            return false;
        }

        if ((int) $user->studentProfile?->id === (int) $student->id) {
            return false;
        }

        if (! $enrolment instanceof StudentEnrolment) {
            return true;
        }

        return (int) $enrolment->student_id === (int) $student->id
            && UserAccessScope::for($user)->canReachDepartment((int) $enrolment->institution_department_id);
    }

    public function manageGallery(User $user, Student $student): bool
    {
        if (! $this->moduleState->isEnabled(ModuleEnum::GALLERY->slug())) {
            return false;
        }

        return $user->studentProfile?->id === $student->id
            && $user->can('manageOwnStudentPersonalDetails:students');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('delete:students', $student);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->can('restore:students', $student);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->can('forceDelete:students', $student);
    }

    public function export(User $user): bool
    {
        return $user->can('export:students');
    }
}
