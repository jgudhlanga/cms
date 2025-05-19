import { CreateApplicationParams } from '@/types/applications';
import { defineStore } from 'pinia';

export const useApplicationFormStore = defineStore('create-application-form', {
    state: (): CreateApplicationParams => {
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
