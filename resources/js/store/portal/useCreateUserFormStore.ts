import { CreateApplicationUserParams } from '@/types/portal';
import { defineStore } from 'pinia';

export const useCreateUserFormStore = defineStore('portal-application-user-form', {
    state: (): CreateApplicationUserParams => {
        return {
            email: '',
            first_name: '',
            last_name: '',
            middle_name: '',
            password: '',
            password_confirmation: '',
            title: null,
            title_id: null,
            gender: null,
            gender_id: null,
        };
    },
    persist: true,
});
