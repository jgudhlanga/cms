export interface User {
    type: string;
    id: string | number;
    attributes: {
        name: string;
        first_name: string;
        middle_name?: string;
        last_name: string;
        email: string;
        tenantId: string | number;
        tenant: string;
        genderId: string | number;
        gender: string;
        titleId: string | number;
        title: string;
        createdAt: string;
        updatedAt: string;
        deletedAt: string;
    };
}
