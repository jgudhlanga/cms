<?php

namespace App\Policies\Institution;

use App\Models\Institution\CourseSyllabus;
use App\Models\Users\User;

class CourseSyllabusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:course-syllabuses');
    }

    public function view(User $user, CourseSyllabus $courseSyllabus): bool
    {
        return $user->can('viewAny:course-syllabuses') || $user->can('view:course-syllabuses');
    }

    public function create(User $user): bool
    {
        return $user->can('create:course-syllabuses');
    }

    public function update(User $user, CourseSyllabus $courseSyllabus): bool
    {
        return $user->can('update:course-syllabuses', $courseSyllabus);
    }

    public function delete(User $user, CourseSyllabus $courseSyllabus): bool
    {
        return $user->can('delete:course-syllabuses', $courseSyllabus);
    }

    public function restore(User $user, CourseSyllabus $courseSyllabus): bool
    {
        return $user->can('restore:course-syllabuses', $courseSyllabus);
    }

    public function forceDelete(User $user, CourseSyllabus $courseSyllabus): bool
    {
        return $user->can('forceDelete:course-syllabuses', $courseSyllabus);
    }

    public function import(User $user): bool
    {
        return $user->can('import:course-syllabuses');
    }

    public function export(User $user): bool
    {
        return $user->can('export:course-syllabuses');
    }

    public function viewAuditTrail(User $user): bool
    {
        return $user->can('viewAuditTrail:course-syllabuses');
    }
}
