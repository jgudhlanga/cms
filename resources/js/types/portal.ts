import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { SelectOption } from '@/types/utils';

export type RegistrationPath = 'zimbabwean' | 'international';

export type CreateApplicationUserParams = {
    email: string;
    first_name: string | null;
    middle_name?: string | null;
    last_name: string | null;
    password: string;
    password_confirmation: string;
    id_number?: string;
    passport_number?: string;
    registration_path?: RegistrationPath;
    acknowledged_advert?: boolean;
};

export type CreateApplicationParams = {
    /** Personal details */
    id_type_id: string | number | null;
    idType: SelectOption | null;
    id_number?: string | null;
    passport_number?: string | null;
    country?: SelectOption | null;
    country_id: string | number | null;
    study_permit_number?: string | null;
    date_of_birth: string | null;
    maritalStatus?: SelectOption | null;
    marital_status_id: string | number | null;
    email: string;
    first_name: string | null;
    last_name: string | null;
    middle_name: string | null;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
    disability_status: 'yes' | 'no' | 'prefer_not_to_say' | null;
    /** Contact details, addresses */
    phone_number?: string | null;
    alt_phone_number?: string | null;
    address_1: string | null;
    address_2: string | null;
    address_3: string | null;
    address_4: string | null;
    /** Next of kin details */
    next_of_kin_name: string;
    relationship: SelectOption | null;
    relationship_id: string | number | null;
    next_of_kin_address_1: string | null;
    next_of_kin_address_2: string | null;
    next_of_kin_address_3: string | null;
    next_of_kin_address_4: string | null;
    next_of_kin_phone_number: string | null;
    /** Programs */
    modeOfStudy: SelectOption | null;
    mode_of_study_id: string | number | null;
    department: SelectOption | null;
    department_id: string | number | null;
    level: SelectOption | null;
    level_id: string | number | null;
    course: SelectOption | null;
    course_id: string | number | null;
    levelRequirements?: DepartmentLevelRequirement | null;
    courseRequirements?: CourseRequirement | null;
    o_level_subject_ids?: Record<string, string> | null;
    o_level_years?: Record<string, string> | null;
    o_level_sittings?: Record<string, SelectOption> | null;
    required_level_completed?: boolean | null;
    required_level_upload?: any;
    read_write_acknowledged?: boolean | null;
    o_level_other_subject_ids?: Record<string, SelectOption> | null;
    o_level_other_grade_ids?: Record<string, string> | null;
    o_level_other_years?: Record<string, string> | null;
    o_level_other_sittings?: Record<string, SelectOption> | null;
    o_level_primary_year?: string | null;
    o_level_primary_sitting?: SelectOption | null;
    o_level_resit_subjects?: Record<string, boolean> | null;
    o_level_other_resit_rows?: Record<string, boolean> | null;
    /** Apprentice track */
    employer?: string | null;
    apprentice_number?: string | null;
    /** Proof of Payment */
    proof_of_payment?: string | null;
    payment_reference?: string | null;
    payment_date?: string | null;
    payment_mode?: 'cash' | 'online' | null;
};

export type ProgramParams = {
    /** Programs */
    modeOfStudy: SelectOption | null;
    mode_of_study_id: string | number | null;
    department: SelectOption | null;
    department_id: string | number | null;
    level: SelectOption | null;
    level_id: string | number | null;
    course: SelectOption | null;
    course_id: string | number | null;
    levelRequirements?: DepartmentLevelRequirement | null;
    courseRequirements?: CourseRequirement | null;
    o_level_subject_ids?: Record<string, string> | null;
    o_level_years?: Record<string, string> | null;
    o_level_sittings?: Record<string, SelectOption> | null;
    required_level_completed?: boolean | null;
    required_level_upload?: any;
    read_write_acknowledged?: boolean | null;
    o_level_other_subject_ids?: Record<string, SelectOption> | null;
    o_level_other_grade_ids?: Record<string, string> | null;
    o_level_other_years?: Record<string, string> | null;
    o_level_other_sittings?: Record<string, SelectOption> | null;
    o_level_primary_year?: string | null;
    o_level_primary_sitting?: SelectOption | null;
    o_level_resit_subjects?: Record<string, boolean> | null;
    o_level_other_resit_rows?: Record<string, boolean> | null;
};
