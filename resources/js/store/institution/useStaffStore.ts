import { ApiFilterResponse } from '@/types/data-pagination';
import { CreateStaffParams } from '@/types/staff';
import { defineStore } from 'pinia';

export const useStaffCreateFormStore = defineStore('staff-create-form', {
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
            date_of_birth: '',
            employee_number: '',
            phone_number: '',
            maritalStatus: null,
            marital_status_id: null,
            role_ids: [],
            employment_type_id: null,
            employmentType: null,
            institution_department_id: '',
        };
    },
    persist: true,
});

export const useStaffDataStore = defineStore('staff-data-form', {
    state: (): ApiFilterResponse => {
        return {
            data: [],
            meta: null,
            links: null,
            filters: null,
            trashedCount: null,
        };
    },
    actions: {
        setStaff(staff: ApiFilterResponse) {
            this.data = staff.data;
            this.meta = staff.meta;
            this.links = staff.links;
            this.filters = staff.filters;
            this.trashedCount = staff.trashedCount;
        },
        clearStaff() {
            this.data = [];
            this.meta = null;
            this.links = null;
            this.filters = null;
            this.trashedCount = 0;
        },
    },
    persist: true,
});
