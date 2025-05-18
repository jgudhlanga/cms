import { SelectOption } from '@/types/utils';

export type CreateApplicationParams = {
    email: string;
    first_name: string | null;
    last_name?: string | null;
    middle_name?: string | null;
    password: string;
    confirm_password: string;
    title: SelectOption | null;
    title_id: string | number | null;
    id_number: string | null;
    passport_number: string | null;
    country: SelectOption | null;
    country_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
    province: SelectOption | null;
    province_id: string | number | null;
    district: SelectOption | null;
    district_id: string | number | null;
    address1: string | null;
    address2: string | null;
    address3: string | null;
    address4: string | null;
};
