<?php

namespace App\Enums\Shared;

enum RoleEnum: string
{
    // Super Users
    case SUPER_ADMINISTRATOR = 'Super Administrator';

    // Executive Academic Admin
    case PRINCIPAL = 'Principal';
    case VICE_PRINCIPAL = 'Vice Principal';
    case REGISTRAR = 'Registrar';
    case SELECTION_OFFICER = 'Selection officer';

    // Academic Staff
    case DEAN = 'Dean';
    case HEAD_OF_DEPARTMENT = 'Head of department';
    case HEAD_OF_DIVISION = 'Head of division';
    case LECTURER = 'Lecturer';
    case LECTURER_IN_CHARGE = 'Lecturer In Charge';
    case SENIOR_LECTURER = 'Senior Lecturer';
    case TUTOR = 'Tutor';
    case RESEARCHER = 'Researcher';

    // Administrative Staff
    case ADMISSIONS_OFFICER = 'Admissions officer';
    case BURSAR = 'Bursar';
    case HR_OFFICER = 'HR officer';
    case ADMINISTRATIVE_ASSISTANT = 'Administrative Assistant';
    case STUDENT_AFFAIRS_OFFICER = 'Student Affairs Officer';

    // Technical & Support Staff
    case IT_SUPPORT_TECHNICIAN = 'IT Support Technician';
    case LAB_TECHNICIAN = 'Lab Technician';
    case LIBRARIAN = 'Librarian';

    // Facilities Staff
    case MAINTENANCE_STAFF = 'Maintenance staff';
    case SECURITY_OFFICER = 'Security officer';
    case CUSTODIAN = 'Custodian';
    case GROUNDS_KEEPER = 'Grounds keeper';
    case TRANSPORT_OFFICER = 'Transport Officer';

    // Student
    case STUDENT = 'Student';

    public function description(): string
    {
        return match ($this) {
            // Super Users
            self::SUPER_ADMINISTRATOR => 'Has full access to all system features and administrative controls.',

            // Executive Academic Admin
            self::PRINCIPAL => 'The chief executive of the college, responsible for overall leadership.',
            self::VICE_PRINCIPAL => 'Assists the principal in managing academic and administrative tasks.',
            self::REGISTRAR => 'Oversees student records, registration, and institutional data.',
            self::SELECTION_OFFICER => 'Manages applicant evaluation and admission selections.',

            // Academic Staff
            self::DEAN => 'Leads an academic faculty and manages teaching and research efforts.',
            self::HEAD_OF_DEPARTMENT => 'Directs the operations of a specific academic department.',
            self::HEAD_OF_DIVISION => 'Oversees a group of departments within a school or faculty.',
            self::LECTURER => 'Delivers lectures and academic content to students.',
            self::LECTURER_IN_CHARGE => 'Leads a teaching team and manages course delivery.',
            self::SENIOR_LECTURER => 'Experienced academic with additional teaching and mentoring responsibilities.',
            self::TUTOR => 'Supports student learning in small group or individual settings.',
            self::RESEARCHER => 'Conducts academic research and publishes scholarly work.',

            // Administrative Staff
            self::ADMISSIONS_OFFICER => 'Handles student applications and enrollment processing.',
            self::BURSAR => 'Manages college finances, budgeting, and student billing.',
            self::HR_OFFICER => 'Administers staff recruitment, payroll, and compliance.',
            self::ADMINISTRATIVE_ASSISTANT => 'Provides clerical and logistical support to departments.',
            self::STUDENT_AFFAIRS_OFFICER => 'Supports student life, welfare, and extracurricular engagement.',

            // Technical & Support Staff
            self::IT_SUPPORT_TECHNICIAN => 'Maintains IT infrastructure and provides user support.',
            self::LAB_TECHNICIAN => 'Prepares lab equipment and assists with practical sessions.',
            self::LIBRARIAN => 'Manages library resources and supports academic research.',

            // Facilities Staff
            self::MAINTENANCE_STAFF => 'Performs repairs and ensures facility upkeep.',
            self::SECURITY_OFFICER => 'Provides safety and security across campus.',
            self::CUSTODIAN => 'Maintains cleanliness and order in college buildings.',
            self::GROUNDS_KEEPER => 'Takes care of lawns, gardens, and outdoor spaces.',
            self::TRANSPORT_OFFICER => 'Coordinates campus transport and vehicle logistics.',

            // Student
            self::STUDENT => 'A learner enrolled in the institution’s academic programs.',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'label' => $type->description(),
            ],
            self::cases()
        );
    }
}
