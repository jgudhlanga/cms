import { Role } from '@/types/acl';
import { User } from '@/types/users';
import { SelectOption } from '@/types/utils';

export type Staff = {
    type: string;
    id: string | number;
    attributes: {
        userId: string | number;
        titleId?: string | number;
        title?: string;
        genderId?: string | number;
        gender?: string;
        maritalStatusId?: string | number;
        maritalStatus?: string;
        raceId?: string | number;
        race?: string;
        employmentTypeId?: string | number;
        employmentType?: string;
        idTypeId?: string | number;
        idType?: string;
        idNumber?: string;
        passportNumber?: string;
        countryId?: string | number;
        country?: string;
        workPermitNumber?: string;
        employeeNumber?: string;
        dateOfBirth?: string;
        religionId?: string | number;
        religion?: string;
        denomination?: string;
        height?: string;
        weight?: string;
        createdAt: string;
        updatedAt: string;
        deletedAt?: string | null;
    };
    relationships: {
        user?: User;
        roles?: Role[];
    };
};

export type CreateStaffParams = {
    institution_department_id: string | number;
    date_of_birth: string | null;
    maritalStatus?: SelectOption | null;
    marital_status_id: string | number | null;
    employmentType?: SelectOption | null;
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
};
