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
    case SELECTION_OFFICER = 'selection-officer';
    case DEAN = 'dean';
    case HEAD_OF_DIVISION = 'head-of-division';
    case HEAD_OF_DEPARTMENT = 'head-of-department';

    // Academic Staff
    case LECTURER = 'lecturer';
    case LECTURER_IN_CHARGE = 'lecturer-in-charge';
    case SENIOR_LECTURER = 'senior-lecturer';

    // Administrative Staff
    case IT_MANAGER = 'it-manager';
    case ACCOUNTANT = 'accountant';
    case BURSAR = 'bursar';
    case HR_OFFICER = 'hr-officer';
    case HR_OFFICER_ASSISTANT = 'hr-officer-assistant';
    case ADMINISTRATIVE_OFFICER = 'administrative-officer';
    case ADMINISTRATIVE_ASSISTANT = 'administrative-assistant';

    // Support and Service Staff
    case IT_SUPPORT_TECHNICIAN = 'it-support-technician';
    case LAB_TECHNICIAN = 'lab-technician';
    case LIBRARIAN = 'librarian';
    case SECURITY_OFFICER = 'security-officer';
    // Student
    case STUDENT = 'student';

    public function name(): string
    {
        return match ($this) {
            self::SUPER_USER => 'Super User',
            self::SUPER_ADMINISTRATOR => 'Super Administrator',

            self::TESC => 'TESC',

            self::PRINCIPAL => 'Principal',
            self::VICE_PRINCIPAL => 'Vice Principal',
            self::REGISTRAR => 'Registrar',
            self::SELECTION_OFFICER => 'Selection Officer',
            self::DEAN => 'Dean',
            self::HEAD_OF_DIVISION => 'Head of Division',
            self::HEAD_OF_DEPARTMENT => 'Head of Department',

            self::LECTURER => 'Lecturer',
            self::LECTURER_IN_CHARGE => 'Lecturer in Charge',
            self::SENIOR_LECTURER => 'Senior Lecturer',

            self::IT_MANAGER => 'IT Manager',
            self::ADMINISTRATIVE_OFFICER => 'Administrative Officer',
            self::ADMINISTRATIVE_ASSISTANT => 'Administrative Assistant',
            self::ACCOUNTANT => 'Accountant',
            self::BURSAR => 'Bursar',
            self::HR_OFFICER => 'HR Officer',
            self::HR_OFFICER_ASSISTANT => 'HR Officer Assistant',

            self::IT_SUPPORT_TECHNICIAN => 'IT Support Technician',
            self::LAB_TECHNICIAN => 'Lab Technician',
            self::LIBRARIAN => 'Librarian',
            self::SECURITY_OFFICER => 'Security Officer',

            self::STUDENT => 'Student',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPER_USER => 'Power user with elevated privileges for system oversight.',
            self::SUPER_ADMINISTRATOR => 'Has unrestricted access to all system functions.',

            self::TESC => 'Tertiary Education Service Council (TESC) group responsible for overseeing tertiary education policies and standards.',

            self::PRINCIPAL => 'The head of the institution.',
            self::VICE_PRINCIPAL => 'Deputy to the Principal.',
            self::REGISTRAR => 'Oversees academic records and administrative operations.',
            self::SELECTION_OFFICER => 'Manages student selection processes.',
            self::DEAN => 'Leads a faculty or academic division.',
            self::HEAD_OF_DIVISION => 'Leads a division and oversees departments within it.',
            self::HEAD_OF_DEPARTMENT => 'Responsible for a specific academic department.',

            self::LECTURER => 'Delivers academic content to students.',
            self::LECTURER_IN_CHARGE => 'Coordinates lecturers within a module.',
            self::SENIOR_LECTURER => 'Senior academic with additional responsibilities.',

            self::IT_MANAGER => 'Oversees IT infrastructure and strategy.',
            self::ADMINISTRATIVE_OFFICER => 'Handles applications and enrollment.',
            self::ACCOUNTANT, self::BURSAR => 'Manages finances of the institution.',
            self::HR_OFFICER => 'Handles staff recruitment and welfare.',
            self::HR_OFFICER_ASSISTANT => 'Helps the HR Officer.',
            self::ADMINISTRATIVE_ASSISTANT => 'Provides administrative support.',

            self::IT_SUPPORT_TECHNICIAN => 'Provides technical support.',
            self::LAB_TECHNICIAN => 'Prepares and maintains lab equipment.',
            self::LIBRARIAN => 'Manages library resources and services.',
            self::SECURITY_OFFICER => 'Maintains safety and security.',

            self::STUDENT => 'Learner enrolled in the institution.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::SUPER_ADMINISTRATOR, self::SUPER_USER => 'super-user',
            self::TESC => 'tesc',
            self::PRINCIPAL, self::VICE_PRINCIPAL, self::REGISTRAR, self::SELECTION_OFFICER,
            self::DEAN, self::HEAD_OF_DEPARTMENT, self::HEAD_OF_DIVISION => 'executive',
            self::LECTURER, self::LECTURER_IN_CHARGE, self::SENIOR_LECTURER => 'academic',
            self::IT_MANAGER, self::ADMINISTRATIVE_OFFICER, self::ACCOUNTANT, self::BURSAR, self::HR_OFFICER,
            self::HR_OFFICER_ASSISTANT, self::ADMINISTRATIVE_ASSISTANT => 'administrative-and-managerial',
            self::IT_SUPPORT_TECHNICIAN, self::LAB_TECHNICIAN, self::LIBRARIAN,
            self::SECURITY_OFFICER => 'service-and-support',
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

