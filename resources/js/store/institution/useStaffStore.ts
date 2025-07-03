import { defineStore } from 'pinia';
import { CreateStaffParams } from '@/types/staff';

export const useStaffStore = defineStore('staff-create-form', {
    state: (): CreateStaffParams => {
        return {
            email: '',
            first_name: '',
            gender: null,
            gender_id: null,
            last_name: '',
            middle_name: '',
            title: null,
            title_id: null,
            country: null,
            country_id: null,
            date_of_birth: '',
            id_number: '',
            id_type: null,
            maritalStatus: null,
            marital_status_id: null,
            passport_number: '',
            role_ids: [],
        };
    },
    persist: true,
});
