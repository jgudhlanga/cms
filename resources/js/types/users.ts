import { Role } from '@/types/acl';

export interface User {
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
        hasProgram?: boolean;
        hasStaffProfile?: boolean;
        staffId?: string | number;
        staffDepartmentIds?: string[] | number[] | null;
        createdAt: string;
        updatedAt: string;
        deletedAt: string;
    };
    relationships: {
        roles: Role[];
    };
}

export type UserParams = {
    email: string;
    first_name: string | null;
    last_name: string | null;
    middle_name: string | null;
    phone_number?: string | null;
    role_ids: Array<string | undefined | null> | null;
    password?: string | null;
    password_confirmation?: string | null;
};
