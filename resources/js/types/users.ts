import { RoleMinimal } from '@/types/acl';
import { Address, Contact } from '@/types/shared';
import { SelectOption } from '@/types/utils';

export type UserPreference = {
    type?: string;
    id?: string | number;
    attributes: {
        userId?: string | number;
        sideBarState?: boolean;
        locale?: string | null;
    };
};

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
        canImpersonate: boolean;
        canBeImpersonated: boolean;
        hasAccessToNonAcademicDepartments: boolean;
        createdAt: string;
        updatedAt: string;
        deletedAt: string;
    };
    relationships: {
        profile: Profile | null;
        roles: RoleMinimal[];
        mainContact?: Contact | null;
        mainAddress?: Address | null;
        preference?: UserPreference | null;
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
    departments?: Array<{ id?: string | number; name?: string } | undefined> | null;
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
}

export type AuthCredentialsUpdate = {
    email: string;
    password: string;
    password_confirmation: string;
    change_email?: boolean;
    change_password?: boolean;
}

export type AuthNamesUpdate = {
    first_name: string;
    middle_name: string;
    last_name: string;
}

