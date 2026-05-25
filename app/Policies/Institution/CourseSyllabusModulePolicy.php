<?php

namespace App\Policies\Institution;

use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;

class CourseSyllabusModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:course-syllabus-modules');
    }

    public function view(User $user, CourseSyllabusModule $courseSyllabusModule): bool
    {
        return $user->can('viewAny:course-syllabus-modules') || $user->can('view:course-syllabus-modules');
    }

    public function create(User $user): bool
    {
        return $user->can('create:course-syllabus-modules');
    }

    public function update(User $user, CourseSyllabusModule $courseSyllabusModule): bool
    {
        return $user->can('update:course-syllabus-modules', $courseSyllabusModule);
    }

    public function delete(User $user, CourseSyllabusModule $courseSyllabusModule): bool
    {
        return $user->can('delete:course-syllabus-modules', $courseSyllabusModule);
    }

    public function restore(User $user, CourseSyllabusModule $courseSyllabusModule): bool
    {
        return $user->can('restore:course-syllabus-modules', $courseSyllabusModule);
    }

    public function forceDelete(User $user, CourseSyllabusModule $courseSyllabusModule): bool
    {
        return $user->can('forceDelete:course-syllabus-modules', $courseSyllabusModule);
    }

    public function import(User $user): bool
    {
        return $user->can('import:course-syllabus-modules');
    }

    public function export(User $user): bool
    {
        return $user->can('export:course-syllabus-modules');
    }

    public function viewAuditTrail(User $user): bool
    {
        return $user->can('viewAuditTrail:course-syllabus-modules');
    }
}
