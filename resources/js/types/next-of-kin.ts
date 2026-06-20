import { SelectOption } from '@/types/utils';

export type NextOfKin = {
    type: string;
    id: string | number;
    attributes: {
        name: string;
        phoneNumber?: string;
        relationship?: string;
        relationshipId?: string | number;
        address1?: string;
        address2?: string;
        address3?: string;
        address4?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type NextOfKinParams = {
    name: string;
    relationship: SelectOption | null;
    relationship_id: string | number | null | undefined;
    address_1: string | null;
    address_2: string | null;
    address_3: string | null;
    address_4: string | null;
    phone_number: string | null;
};
