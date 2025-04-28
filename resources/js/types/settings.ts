import { SelectOption } from '@/types/utils';

export type Gender = {
    type?: string;
    id?: string;
    attributes: {
        title: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type GenderParams = {
    title: string;
    description?: string;
};

export type Language = {
    type?: string;
    id?: string;
    attributes: {
        title: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type LanguageParams = {
    title: string;
    description?: string;
};

export type Province = {
    type?: string;
    id?: string;
    attributes: {
        title: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type ProvinceParams = {
    title: string;
    description?: string;
};

export type Race = {
    type?: string;
    id?: string;
    attributes: {
        title: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type RaceParams = {
    title: string;
    description?: string;
};

export type Status = {
    type?: string;
    id?: string;
    attributes: {
        title: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type StatusParams = {
    title: string;
    description?: string;
};

export type Title = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type TitleParams = {
    name: string;
    description?: string;
};

export type AddressType = {
    type?: string;
    id?: string;
    attributes: {
        title: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type AddressTypeParams = {
    title: string;
    description?: string;
};

export type Relationship = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type RelationshipParams = {
    name: string;
    description?: string;
};

export type District = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        provinceId?: number | string | undefined;
        province?: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type DistrictParams = {
    name: string;
    province_id?: string | number | undefined;
    province?: SelectOption | null;
    description?: string;
};
