<?php

namespace App\Enums\Rbac;

enum ScopeLevelEnum: string
{
    case College = 'college';
    case Division = 'division';
    case Department = 'department';
    case LecturerModules = 'lecturer_modules';
    case Hostel = 'hostel';
    case AssignedHostels = 'assigned_hostels';
}
