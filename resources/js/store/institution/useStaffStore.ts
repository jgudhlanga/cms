import { ApiFilterResponse } from '@/types/data-pagination';
import { CreateStaffParams, Staff } from '@/types/staff';
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
    actions: {
        setStaffFormData(staff?: Staff) {
           this.email = staff?.relationships?.user?.attributes?.email ?? '';
                this.first_name = staff?.relationships?.user?.attributes?.firstname ?? '';
                this.gender = {label: staff?.attributes?.gender ?? '', value: Number(staff?.attributes?.genderId ?? '') };
                this.gender_id = staff?.attributes?.genderId ?? null;
                this.last_name = staff?.relationships?.user?.attributes?.lastname ?? '';
                this.middle_name = staff?.relationships?.user?.attributes?.middleName ?? '';
                this.title = staff?.attributes?.title ? { label: staff.attributes.title, value: Number(staff.attributes.titleId ?? '') } : null;
                this.title_id = staff?.attributes?.titleId ?? null;
                this.date_of_birth = staff?.attributes?.dateOfBirth ?? '';
                this.employee_number = staff?.attributes?.employeeNumber ?? '';
                this.phone_number = staff?.relationships?.user?.attributes?.phoneNumber ?? '';
                this.maritalStatus = staff?.attributes?.maritalStatus ? { label: staff.attributes.maritalStatus, value: Number(staff.attributes.maritalStatusId ?? '') } : null;
                this.marital_status_id = staff?.attributes?.maritalStatusId ?? null;
                this.role_ids = staff?.relationships?.roles ? staff.relationships.roles.map(role => role.id) : [];
                this.employment_type_id = staff?.attributes?.employmentTypeId ?? null;
                this.employmentType = { label: staff?.attributes?.employmentType ?? '', value: Number(staff?.attributes.employmentTypeId ?? '') };
                this.institution_department_id = ''; // This would need to be set based on your application's logic
        }
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
