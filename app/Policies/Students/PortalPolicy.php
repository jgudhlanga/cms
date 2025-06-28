<?php

namespace App\Policies\Students;

use App\Enums\Shared\PermissionEnum;
use App\Models\Users\User;

class PortalPolicy
{
    public function viewStudentDashboard(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_OWN_STUDENT_DASHBOARD);
    }

    public function manageStudentPersonalDetails(User $user): bool
    {
        return $user->can(PermissionEnum::MANAGE_OWN_STUDENT_PERSONAL_DETAILS);
    }

    public function manageStudentProgramDetails(User $user): bool
    {
        return $user->can(PermissionEnum::MANAGE_OWN_STUDENT_PROGRAM_DETAILS);
    }

    public function manageStudentSponsors(User $user): bool
    {

        return $user->can(PermissionEnum::MANAGE_OWN_STUDENT_SPONSOR_DETAILS);
    }

    public function manageStudentContacts(User $user): bool
    {
        return $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function manageStudentFinancialRecords(User $user): bool
    {
        return $user->can(PermissionEnum::MANAGE_OWN_STUDENT_FINANCIAL_DETAILS);
    }

    public function manageStudentAcademicRecords(User $user): bool
    {
        return $user->can(PermissionEnum::MANAGE_OWN_STUDENT_ACADEMIC_DETAILS);
    }
}
