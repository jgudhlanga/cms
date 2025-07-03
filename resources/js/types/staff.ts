import { Role } from '@/types/acl';
import { DataFilters, DataListProps } from '@/types/data-pagination';
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
        employmentType?: string | number;
        idType?: string;
        idNumber?: string;
        passportNumber?: string;
        countryId?: string | number;
        country?: string;
        workPermitNumber?: string;
        employeeNumber?: string;
        staffIdNumber?: string;
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

export type StaffParams = {
    id_type: string;
    id_number?: string | null;
    passport_number?: string | null;
    country?: SelectOption | null;
    country_id: string | number | null;
    work_permit_number?: string | null;
    date_of_birth: string | null;
    maritalStatus?: SelectOption | null;
    marital_status_id: string | number | null;
    email: string;
    first_name: string;
    last_name: string;
    middle_name: string;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
    employmentType: SelectOption | null;
    employment_type_id: string | number | null;
    employee_number?: string | null;
};

export type StaffSearchData = {
    staff: DataListProps;
    filters: DataFilters;
    trashedCount: any;
};
