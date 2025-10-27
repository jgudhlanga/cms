<?php

namespace App\Enums\Acl;

enum RoleEnum: string
{
    // Super Users
    case SUPER_USER = 'super-user';
    case SUPER_ADMINISTRATOR = 'super-administrator';

    // TESC
    case TESC = 'tesc';

    // Executive
    case PRINCIPAL = 'principal';
    case VICE_PRINCIPAL = 'vice-principal';
    case REGISTRAR = 'registrar';
    case DEAN = 'dean';
    case BURSAR = 'bursar';
    case LIBRARIAN = 'librarian';

    // Academic Staff
    case LECTURER = 'lecturer';
    case LECTURER_IN_CHARGE = 'lecturer-in-charge';
    case SENIOR_LECTURER = 'senior-lecturer';
    case HEAD_OF_DIVISION = 'head-of-division';
    case HEAD_OF_DEPARTMENT = 'head-of-department';
    case SELECTION_OFFICER = 'selection-officer';

    // Managerial Staff
    case IT_MANAGER = 'it-manager';
    case ACCOUNTANT = 'accountant';
    case HR_OFFICER = 'hr-officer';
    case ADMINISTRATIVE_OFFICER = 'administrative-officer';

    // Administrative Staff
    case ACCOUNTANT_ASSISTANT = 'accountant-assistant';
    case HR_OFFICER_ASSISTANT = 'hr-officer-assistant';
    case ADMINISTRATIVE_ASSISTANT = 'administrative-assistant';
    case IT_SYSTEM_ADMINISTRATOR = 'it-system-administrator';

    // Support and Service Staff
    case IT_SUPPORT_TECHNICIAN = 'it-support-technician';
    case LAB_TECHNICIAN = 'lab-technician';

    case SECURITY_OFFICER = 'security-officer';

    // Student
    case STUDENT = 'student';
    case REGISTRY_OFFICER = 'registry-officer';

    public function name(): string
    {
        return match ($this) {
            // Superusers
            self::SUPER_USER => 'Super User',
            self::SUPER_ADMINISTRATOR => 'Super Administrator',

            // Tesc
            self::TESC => 'TESC',

            // Executive
            self::PRINCIPAL => 'Principal',
            self::VICE_PRINCIPAL => 'Vice Principal',
            self::REGISTRAR => 'Registrar',
            self::DEAN => 'Dean',
            self::BURSAR => 'Bursar',
            self::LIBRARIAN => 'Librarian',
            self::REGISTRY_OFFICER => 'Registry Officer',

            // Academic Staff
            self::LECTURER => 'Lecturer',
            self::LECTURER_IN_CHARGE => 'Lecturer in Charge',
            self::SENIOR_LECTURER => 'Senior Lecturer',
            self::SELECTION_OFFICER => 'Selection Officer',
            self::HEAD_OF_DIVISION => 'Head of Division',
            self::HEAD_OF_DEPARTMENT => 'Head of Department',

            // Managerial Staff
            self::IT_MANAGER => 'IT Manager',
            self::ADMINISTRATIVE_OFFICER => 'Administrative Officer',
            self::ACCOUNTANT => 'Accountant',
            self::HR_OFFICER => 'HR Officer',

            // Administrative Staff
            self::ADMINISTRATIVE_ASSISTANT => 'Administrative Assistant',
            self::HR_OFFICER_ASSISTANT => 'HR Officer Assistant',
            self::ACCOUNTANT_ASSISTANT => 'Accountant Assistant',
            self::IT_SYSTEM_ADMINISTRATOR => 'IT Systems Administrator',

            // Support and Service Staff
            self::IT_SUPPORT_TECHNICIAN => 'IT Support Technician',
            self::LAB_TECHNICIAN => 'Lab Technician',
            self::SECURITY_OFFICER => 'Security Officer',

            // Student
            self::STUDENT => 'Student',
        };
    }

    public function description(): string
    {
        return match ($this) {
            // Super Users
            self::SUPER_USER => 'Power user with elevated privileges for system oversight.',
            self::SUPER_ADMINISTRATOR => 'Has unrestricted access to all system functions.',

            // Test
            self::TESC => 'Tertiary Education Service Council (TESC) group responsible for overseeing tertiary education policies and standards.',

            // Executive
            self::PRINCIPAL => 'The head of the institution.',
            self::VICE_PRINCIPAL => 'Deputy to the Principal.',
            self::REGISTRAR => 'Oversees academic records and administrative operations.',
            self::DEAN => 'Leads a faculty or academic division.',
            self::BURSAR => 'Oversees and Manages finances of the institution.',
            self::LIBRARIAN => 'Manages library resources and services.',
            self::REGISTRY_OFFICER => 'Verifies and manages enrolments.',

            // Academic Staff
            self::HEAD_OF_DIVISION => 'Leads a division and oversees departments within it.',
            self::HEAD_OF_DEPARTMENT => 'Responsible for a specific academic department.',
            self::LECTURER => 'Delivers academic content to students.',
            self::LECTURER_IN_CHARGE => 'Coordinates lecturers within a module.',
            self::SENIOR_LECTURER => 'Senior academic with additional responsibilities.',
            self::SELECTION_OFFICER => 'Manages student selection processes.',

            // Managerial Staff
            self::IT_MANAGER => 'Oversees IT infrastructure and strategy.',
            self::ADMINISTRATIVE_OFFICER => 'Handles applications and enrollment.',
            self::ACCOUNTANT => 'Manages finances of the institution.',
            self::HR_OFFICER => 'Handles staff recruitment and welfare.',

            // Administrative Staff
            self::HR_OFFICER_ASSISTANT => 'Helps the HR Officer.',
            self::ADMINISTRATIVE_ASSISTANT => 'Provides administrative support.',
            self::ACCOUNTANT_ASSISTANT => 'Provides support to Accountant.',
            self::IT_SYSTEM_ADMINISTRATOR => 'Provides IT systems administration.',

            // Support and Service Staff
            self::IT_SUPPORT_TECHNICIAN => 'Provides technical support.',
            self::LAB_TECHNICIAN => 'Prepares and maintains lab equipment.',
            self::SECURITY_OFFICER => 'Maintains safety and security.',

            // Student
            self::STUDENT => 'Learner enrolled in the institution.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::SUPER_ADMINISTRATOR, self::SUPER_USER => 'super-user',
            self::TESC => 'tesc',
            self::PRINCIPAL, self::VICE_PRINCIPAL, self::BURSAR, self::REGISTRAR, self::LIBRARIAN, self::DEAN, self::REGISTRY_OFFICER => 'executive',
            self::HEAD_OF_DEPARTMENT, self::HEAD_OF_DIVISION, self::SELECTION_OFFICER, self::LECTURER, self::LECTURER_IN_CHARGE, self::SENIOR_LECTURER => 'academic',
            self::HR_OFFICER, self::ACCOUNTANT, self::ADMINISTRATIVE_OFFICER, self::IT_MANAGER => 'managerial',
            self::HR_OFFICER_ASSISTANT, self::ACCOUNTANT_ASSISTANT, self::IT_SYSTEM_ADMINISTRATOR, self::ADMINISTRATIVE_ASSISTANT => 'administrative',
            self::IT_SUPPORT_TECHNICIAN, self::LAB_TECHNICIAN, self::SECURITY_OFFICER => 'service-and-support',
            self::STUDENT => 'student',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'name' => $type->name(),
                'description' => $type->description(),
                'group' => $type->group(),
            ],
            self::cases()
        );
    }
}

