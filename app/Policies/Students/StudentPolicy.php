<?php

namespace App\Policies\Students;

use App\Enums\Shared\ModuleEnum;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;

class StudentPolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:students');
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
