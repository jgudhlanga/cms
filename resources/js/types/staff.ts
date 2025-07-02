import { User } from '@/types/users';

export type Staff = {
    type: string;
    id: string | number;
    attributes: {
        user: User;
        department: string;
        institutionDepartmentId: string|number;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    }
}

export type StaffParams = {
    first_name: string,
    middle_name?: string,
    last_name: string,
}
