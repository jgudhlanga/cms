import { CreateApplicationUserParams, RegistrationPath } from '@/types/portal';
import { defineStore } from 'pinia';

export const useCreateUserFormStore = defineStore('portal-application-user-form', {
    state: (): CreateApplicationUserParams => {
        return {
            email: '',
            first_name: '',
            middle_name: '',
            last_name: '',
            password: '',
            password_confirmation: '',
            id_number: '',
            passport_number: '',
            registration_path: 'zimbabwean',
        };
    },
    persist: true,
});

export type { RegistrationPath };
