<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import EmploymentTypeComboSelect from '@/components/core/form/combobox/EmploymentTypeComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import RoleSelect from '@/components/core/form/select/RoleSelect.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useStaff } from '@/composables/institution/useStaff';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { useStaffCreateFormStore } from '@/store/institution/useStaffStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { CreateStaffParams } from '@/types/staff';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import BaseButton from '../../../components/core/button/BaseButton.vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = department.id?.toString() ?? '';
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department, href: route('institution-departments.show', institutionDepartmentId) },
    { transKey: 'create_staff' },
];
// Store
const store = useStaffCreateFormStore();
const {
    email,
    first_name,
    gender,
    phone_number,
    last_name,
    middle_name,
    title,
    date_of_birth,
    maritalStatus,
    role_ids,
    employmentType,
    employee_number,
} = storeToRefs(store);
const form = useForm<CreateStaffParams>({
    email: '',
    first_name: '',
    gender: null,
    gender_id: null,
    last_name: '',
    middle_name: '',
    title: null,
    title_id: null,
    employmentType: null,
    date_of_birth: '',
    phone_number: '',
    maritalStatus: null,
    marital_status_id: null,
    role_ids: [],
    institution_department_id: '',
    employment_type_id: '',
    employee_number: '',
});

const { saveStaff, createFormSchema } = useStaff();

const updateForm = () => {
    Object.assign(form, {
        email: email.value,
        phone_number: phone_number?.value ?? '',
        first_name: first_name.value,
        gender: gender.value,
        gender_id: gender.value?.value ?? '',
        last_name: last_name.value,
        middle_name: middle_name.value ?? '',
        title: title.value,
        title_id: title.value?.value ?? '',
        date_of_birth: date_of_birth.value ?? '',
        maritalStatus: maritalStatus?.value,
        marital_status_id: maritalStatus?.value?.value ?? null,
        role_ids: role_ids.value ?? [],
        institution_department_id: department.id,
        employmentType: employmentType?.value,
        employment_type_id: employmentType?.value?.value ?? null,
        employee_number: employee_number?.value ?? null,
    });
};

const save = () => {
    updateForm();
    try {
        createFormSchema().parse(form);
        saveStaff(form, institutionDepartmentId);
    } catch (error: any) {
        form.setError(error.format());
    }
};
const onlyRoles = "head-of-department,head-of-division,lecturer,lecturer-in-charge,senior-lecturer";
</script>

<template>
    <Head :title="$t('trans.create_staff')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => save()">
            <BaseCard :title="$t('trans.personal_details')" :description="$t('trans.personal_details_description')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <TitleComboSelect :form="form" v-model="title" :error="form.errors.title" :label-uppercase="true" :is-required="true" />
                    <BaseInput
                        input-id="first_name"
                        :label="$t('trans.first_name')"
                        v-model="first_name"
                        placeholder="enter firstname"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'first_name')"
                        :error="form.errors.first_name"
                    />
                    <BaseInput
                        input-id="middle_name"
                        :label="$t('trans.middle_name')"
                        placeholder="enter middlename"
                        v-model="middle_name"
                        :label-uppercase="true"
                    />
                    <BaseInput
                        input-id="last_name"
                        :label="$t('trans.last_name')"
                        placeholder="enter lastname / surname"
                        v-model="last_name"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'last_name')"
                        :error="form.errors.last_name"
                    />
                    <GenderComboSelect :form="form" v-model="gender" :error="form.errors.gender" :label-uppercase="true" :is-required="true" />
                    <MaritalStatusComboSelect
                        :form="form"
                        v-model="maritalStatus"
                        :error="form.errors.maritalStatus"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                    <EmploymentTypeComboSelect
                        :form="form"
                        v-model="employmentType"
                        :error="form.errors.employmentType"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                    <BaseInput
                        input-id="employee_number"
                        :label="$t('trans.employee_number')"
                        placeholder="enter EC Number"
                        v-model="employee_number"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'employee_number')"
                        :error="form.errors.employee_number"
                    />
                    <DateOfBirth
                        v-model="date_of_birth"
                        :is-required="true"
                        :label-uppercase="true"
                        :teleport="true"
                        :error="form.errors.date_of_birth"
                        @update:model-value="clearFormErrors(form, 'date_of_birth')"
                    />
                    <EmailAddress
                        v-model="email"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'email')"
                        :error="form.errors.email"
                    />
                    <PhoneNumber
                        v-model="phone_number"
                        placeholder="enter phone number"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'phone_number')"
                        :error="form.errors.phone_number"
                    />
                </div>
            </BaseCard>
            <div class="my-8 grid grid-cols-1 gap-3 md:grid-cols-2">
                <BaseCard :title="$tChoice('trans.role', 2)" :description="$t('trans.role_details_description')">
                    <div class="mt-4 grid grid-cols-1">
                        <RoleSelect
                            :url="`api/v1/acl/roles?page_size=all&only=${onlyRoles}`"
                            :label-uppercase="true"
                            :is-multi="true"
                            :is-searchable="true"
                            v-model="role_ids" />
                    </div>
                </BaseCard>
            </div>
            <div class="mt-6 flex w-full justify-center space-x-3 border-t-[1px] px-6 py-5">
                <BaseButton type="button" :variant="ColorVariant.shade" @click="() => {}" :size="ButtonSize.lg">
                    {{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :processing="form.processing" :disabled="form.processing" :size="ButtonSize.lg">
                    {{ $t('trans.save') }}
                </BaseButton>
                <slot name="action-button" />
            </div>
        </form>
    </PageContainer>
</template>
