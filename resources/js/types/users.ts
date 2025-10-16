import { Role } from '@/types/acl';
import { SelectOption } from '@/types/utils';

export type User = {
    type: string;
    id: string | number;
    attributes: {
        name: string;
        firstname: string;
        middleName?: string;
        lastname: string;
        avatarUrl?: string;
        email: string;
        phoneNumber?: string;
        tenantId: string | number;
        tenant?: string;
        statusId: string | number;
        status?: string;
        loginCount?: string | number;
        lastLoginAt?: string;
        hasStudentProfile?: boolean;
        studentId?: string | number;
        hasProgram?: boolean;
        hasStaffProfile?: boolean;
        idNumber?: string;
        staffId?: string | number;
        hasAccessToNonAcademicDepartments: boolean;
        createdAt: string;
        updatedAt: string;
        deletedAt: string;
    };
    relationships: {
        profile: Profile | null;
        roles: Role[];
    };
};

export type Profile = {
    title: string | null;
    titleId: string | number | null;
    gender: string | null;
    genderId: string | number | null;
    maritalStatus: string | null;
    maritalStatusId: string | number | null;
    employmentType: string | null;
    employmentTypeId: string | number | null;
    employeeNumber: string | null;
    dateOfBirth: string | null;
    idNumber: string | null;
    passportNumber: string | null;
    idType: string | null;
    idTypeId: string | number | null;
    country: string | null;
    countryId: string | number | null;
    departments?: Array<{ id?: string | number; name?: string }|undefined> | null;
};

export type StudentUserEditParams = {
    id_type_id: string | number | null;
    idType: SelectOption | null;
    id_number?: string | null;
    passport_number?: string | null;
    country?: SelectOption | null;
    country_id: string | number | null;
    date_of_birth: string | null;
    maritalStatus?: SelectOption | null;
    marital_status_id: string | number | null;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
    email: string;
    phone_number?: string;
    first_name: string;
    last_name: string;
    middle_name: string;
};

export type UserStaffParams = {
    date_of_birth: string | null;
    maritalStatus?: SelectOption | null;
    marital_status_id: string | number | null;
    employmentType?: SelectOption | null;
    department?: SelectOption | null;
    employment_type_id: string | number | null;
    email: string;
    phone_number?: string;
    first_name: string;
    last_name: string;
    middle_name: string;
    employee_number: string;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
    role_ids: Array<string | undefined | null> | null;
    department_ids: Array<string | undefined | null> | null;
};
