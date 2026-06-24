<?php

namespace App\Policies\Students;

use App\Models\Users\User;

class PortalPolicy
{
    public function viewStudentDashboard(User $user): bool
    {
        return $user->can('viewOwnDashboard:students');
    }

    public function manageStudentPersonalDetails(User $user): bool
    {
        return $user->can('manageOwnStudentPersonalDetails:students');
    }

    public function manageStudentApplicationDetails(User $user): bool
    {
        return $user->can('manageOwnStudentApplicationDetails:students');
    }

    public function manageStudentSponsors(User $user): bool
    {

        return $user->can('manageOwnStudentSponsorDetails:students');
    }

    public function manageStudentContacts(User $user): bool
    {
        return $user->can('manageOwnStudentContactDetails:students');
    }

    public function manageStudentFinancialRecords(User $user): bool
    {
        return $user->can('manageOwnStudentFinancialDetails:students');
    }

    public function manageStudentAcademicRecords(User $user): bool
    {
        return $user->can('manageOwnStudentAcademicDetails:students');
    }

    public function manageStudentAccommodationDetails(User $user): bool
    {
        return $user->can('manageOwnStudentAccommodationDetails:students')
            || $user->can('manageOwnStudentPersonalDetails:students');
    }
}
