import { SelectOption } from '@/types/utils';

export type CreateApplicationParams = {
    email: string;
    first_name: string | null;
    last_name: string | null;
    middle_name?: string | null;
    password: string;
    password_confirmation: string;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
};
