<?php

namespace App\Policies\AcademicCalendars;

use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\Users\User;
use App\Support\Rbac\UserAccessScope;

/**
 * Lecturers request and follow their own extensions; approvers (HOD within their departments, VP
 * college-wide) decide them. Nobody may decide their own request.
 */
class CourseWorkCaptureExtensionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:course-work-extensions') || $user->can('request:course-work-extensions');
    }

    public function viewDecisionQueue(User $user): bool
    {
        return $user->can('approve:course-work-extensions') || $user->can('revoke:course-work-extensions');
    }

    public function view(User $user, CourseWorkCaptureExtension $extension): bool
    {
        if ((int) $extension->requested_by === (int) $user->id) {
            return true;
        }

        return $this->viewDecisionQueue($user) && $this->reachesDepartment($user, $extension);
    }

    public function request(User $user): bool
    {
        return $user->can('request:course-work-extensions');
    }

    public function approve(User $user, CourseWorkCaptureExtension $extension): bool
    {
        return $user->can('approve:course-work-extensions')
            && (int) $extension->requested_by !== (int) $user->id
            && $this->reachesDepartment($user, $extension);
    }

    public function reject(User $user, CourseWorkCaptureExtension $extension): bool
    {
        return $this->approve($user, $extension);
    }

    public function revoke(User $user, CourseWorkCaptureExtension $extension): bool
    {
        return $user->can('revoke:course-work-extensions') && $this->reachesDepartment($user, $extension);
    }

    public function cancel(User $user, CourseWorkCaptureExtension $extension): bool
    {
        return (int) $extension->requested_by === (int) $user->id
            && $extension->status === CourseWorkExtensionStatusEnum::Pending;
    }

    private function reachesDepartment(User $user, CourseWorkCaptureExtension $extension): bool
    {
        return UserAccessScope::for($user)->canReachDepartment((int) $extension->institution_department_id);
    }
}
