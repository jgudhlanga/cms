import { CreateApplicationParams } from '@/types/portal';
import { defineStore } from 'pinia';

export const useCreateApplicationFormStore = defineStore('portal-application-form', {
    state: (): CreateApplicationParams => {
        return {
            email: '',
            first_name: '',
            gender: null,
            gender_id: null,
            last_name: null,
            middle_name: null,
            title: null,
            title_id: null,
            address_1: '',
            address_2: '',
            address_3: '',
            address_4: '',
            address_5: '',
            alt_phone_number: '',
            country: null,
            country_id: null,
            date_of_birth: '',
            id_number: '',
            id_type: null,
            maritalStatus: null,
            marital_status_id: null,
            next_of_kin_address_1: '',
            next_of_kin_address_2: '',
            next_of_kin_address_3: '',
            next_of_kin_address_4: '',
            next_of_kin_address_5: '',
            next_of_kin_name: '',
            next_of_kin_phone_number: '',
            passport_number: '',
            phone_number: '',
            relationship: null,
            relationship_id: null,
            study_permit_number: ''
        };
    },
    persist: true,
});
