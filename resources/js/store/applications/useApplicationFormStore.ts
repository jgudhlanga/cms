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
            title: null,
            title_id: null,
            id_number: null,
            passport_number: null,
            country: null,
            country_id: null,
            gender: null,
            gender_id: null,
        };
    },
    persist: true,
});
