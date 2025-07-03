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
        tenantId: string | number;
        tenant?: string;
        statusId: string | number;
        status?: string;
        loginCount?: string | number;
        lastLoginAt?: string;
        createdAt: string;
        updatedAt: string;
        deletedAt: string;
    };
}
