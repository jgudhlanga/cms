import { SelectOption } from '@/types/utils';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';

export type CreateApplicationUserParams = {
    email: string;
    first_name: string | null;
    last_name: string | null;
    middle_name?: string | null;
    password: string;
    password_confirmation: string;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
};

export type CreateApplicationParams = {
    /** Personal details */
    id_type: string;
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
    department: SelectOption | null;
    department_id: string | number | null;
    level: SelectOption | null;
    level_id: string | number | null;
    course: SelectOption | null;
    course_id: string | number | null;
    levelRequirements?: DepartmentLevelRequirement | null
};
