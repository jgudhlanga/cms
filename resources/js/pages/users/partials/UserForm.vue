<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import EmploymentTypeComboSelect from '@/components/core/form/combobox/EmploymentTypeComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import InstitutionDepartmentSelect from '@/components/core/form/select/InstitutionDepartmentSelect.vue';
import RoleSelect from '@/components/core/form/select/RoleSelect.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useUsers } from '@/composables/users/useUsers';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import { clearFormErrors } from '@/lib/forms';
import { User, UserStaffParams } from '@/types/users';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    edit?: boolean;
    user?: User;
}

const props = withDefaults(defineProps<Props>(), {
    edit: false,
});
const { user } = props;

const form = useForm<UserStaffParams>({
    email: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    phone_number: '',
    role_ids: [],
    gender: null,
    gender_id: null,
    title: null,
    title_id: null,
    employmentType: null,
    date_of_birth: '',
    maritalStatus: null,
    marital_status_id: null,
    department_ids: [],
    employment_type_id: '',
    employee_number: '',
});

const { validateFormSchema, saveStaffUser } = useUsers();
const { navigateTo } = useUtils();

const isValidating = ref(false);
const save = async () => {
    try {
        isValidating.value = true;
        form.title_id = String(form.title?.value);
        form.gender_id = String(form.gender?.value);
        form.marital_status_id = String(form.maritalStatus?.value);
        form.employment_type_id = String(form.employmentType?.value);
        const userId = String(user?.id);
        const staffId = user?.attributes?.staffId ? String(user?.attributes?.staffId) : '';
        if (!form.department_ids || form.department_ids.filter(Boolean).length === 0) {
            errorAlert('Department is required');
            return;
        }
        if (!form.role_ids || form.role_ids.filter(Boolean).length === 0) {
            errorAlert('User role is required');
            return;
        }
        await validateFormSchema(userId, staffId).parseAsync(form);
        saveStaffUser(form, userId);
    } catch (error: any) {
        form.setError(error.format());
    } finally {
        isValidating.value = false;
    }
};

onMounted(() => {
    if (props.edit && user) {
        form.first_name = user.attributes.firstname ?? '';
        form.last_name = user.attributes.lastname ?? '';
        form.middle_name = user.attributes.middleName ?? '';
        form.phone_number = user.attributes.phoneNumber ?? '';
        form.email = user.attributes.email ?? '';
        form.role_ids = user?.relationships?.roles?.map((role) => role.id);
        form.department_ids = user?.relationships?.profile?.departments?.map((d) => d?.id) as (string | null | undefined)[];
        form.gender = { value: Number(user?.relationships?.profile?.genderId ?? ''), label: user?.relationships?.profile?.gender ?? '' };
        form.title = { value: Number(user?.relationships?.profile?.titleId ?? ''), label: user?.relationships?.profile?.title ?? '' };
        form.maritalStatus = {
            value: Number(user?.relationships?.profile?.maritalStatusId ?? ''),
            label: user?.relationships?.profile?.maritalStatus ?? '',
        };
        form.employmentType = {
            value: Number(user?.relationships?.profile?.employmentTypeId ?? ''),
            label: user?.relationships?.profile?.employmentType ?? '',
        };
        form.employee_number = user?.relationships?.profile?.employeeNumber ?? '';
        form.date_of_birth = user?.relationships?.profile?.dateOfBirth ?? '';
    }
});
</script>

<template>
    <form @submit.prevent="() => save()">
        <BaseCard :title="$t('trans.personal_details')" :description="$t('trans.personal_details_description')">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <BaseInput
                    input-id="first_name"
                    :label="$t('trans.first_name')"
                    v-model="form.first_name"
                    :placeholder="$t('trans.ui_enter_firstname')"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'first_name')"
                    :error="form.errors.first_name"
                />
                <BaseInput
                    input-id="middle_name"
                    :label="$t('trans.middle_name')"
                    :placeholder="$t('trans.ui_enter_middlename')"
                    v-model="form.middle_name"
                    :label-uppercase="true"
                />
                <BaseInput
                    input-id="last_name"
                    :label="$t('trans.last_name')"
                    :placeholder="$t('trans.ui_enter_lastname_surname')"
                    v-model="form.last_name"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'last_name')"
                    :error="form.errors.last_name"
                />
                <PhoneNumber
                    v-model="form.phone_number"
                    :placeholder="$t('trans.ui_enter_phone_number')"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'phone_number')"
                    :error="form.errors.phone_number"
                />
            </div>
        </BaseCard>
        <div class="mt-6 flex flex-col justify-center">
            <BaseCard :title="$t('trans.ui_staff_profile')" :description="$t('trans.ui_staff_profile_details')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <TitleComboSelect :form="form" v-model="form.title" :error="form.errors.title" :label-uppercase="true" :is-required="true" />
                    <GenderComboSelect :form="form" v-model="form.gender" :error="form.errors.gender" :label-uppercase="true" :is-required="true" />
                    <MaritalStatusComboSelect
                        :form="form"
                        v-model="form.maritalStatus"
                        :error="form.errors.maritalStatus"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                    <EmploymentTypeComboSelect
                        :form="form"
                        v-model="form.employmentType"
                        :error="form.errors.employmentType"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                    <BaseInput
                        input-id="employee_number"
                        :label="$t('trans.employee_number')"
                        :placeholder="$t('trans.ui_enter_ec_number')"
                        v-model="form.employee_number"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'employee_number')"
                        :error="form.errors.employee_number"
                    />
                    <DateOfBirth
                        v-model="form.date_of_birth"
                        :is-required="true"
                        :label-uppercase="true"
                        :teleport="true"
                        :error="form.errors.date_of_birth"
                        @update:model-value="clearFormErrors(form, 'date_of_birth')"
                    />
                    <InstitutionDepartmentSelect
                        :label-uppercase="true"
                        :is-multi="true"
                        :is-searchable="true"
                        :is-required="true"
                        v-model="form.department_ids"
                        :url="route('v1.institution-departments.index', { page_size: 'all' })"
                    />
                </div>
            </BaseCard>
        </div>
        <div class="mt-6 flex flex-col justify-center">
            <BaseCard :title="$t('trans.ui_login_profile')" :description="$t('trans.ui_use_will_create_change_password_through_the_forgot_password')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <EmailAddress
                        v-model="form.email"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'email')"
                        :error="form.errors.email"
                    />
                    <RoleSelect
                        :url="`api/v1/rbac/roles?page_size=all`"
                        :label-uppercase="true"
                        :is-multi="true"
                        :is-searchable="true"
                        :is-required="true"
                        v-model="form.role_ids"
                    />
                </div>
            </BaseCard>
        </div>
        <div class="mt-6 flex w-full justify-center space-x-3 px-6 py-5">
            <BaseButton type="button" :variant="ColorVariant.shade" @click="() => navigateTo(route('users.index'))">
                {{ $t('trans.back') }}
            </BaseButton>
            <BaseButton :processing="form.processing || isValidating">
                {{ $t('trans.save') }}
            </BaseButton>
        </div>
    </form>
</template>
