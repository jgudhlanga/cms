import { CreateApplicationUserParams } from '@/types/portal';
import { defineStore } from 'pinia';

export const useCreateUserFormStore = defineStore('portal-application-user-form', {
    state: (): CreateApplicationUserParams => {
        return {
            email: '',
            first_name: '',
            last_name: '',
            password: '',
            password_confirmation: '',
        };
    },
    persist: true,
});
