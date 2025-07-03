<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import RoleSelect from '@/components/core/form/select/RoleSelect.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import PassportNumber from '@/components/core/form/text/PassportNumber.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { ID_TYPES } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useStaffStore } from '@/store/institution/useStaffStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { CreateStaffParams } from '@/types/staff';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { ref } from 'vue';
import BaseButton from '../../../components/core/button/BaseButton.vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department },
    { transKey: 'create_staff' },
];
// Store
const {
    email,
    first_name,
    gender,
    last_name,
    middle_name,
    title,
    country,
    date_of_birth,
    id_number,
    id_type,
    maritalStatus,
    passport_number,
    role_ids,
} = storeToRefs(useStaffStore());
const form = useForm<CreateStaffParams>({
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
    id_type: '',
    maritalStatus: null,
    marital_status_id: null,
    passport_number: '',
    role_ids: [],
});

const idTypes = ID_TYPES;

const onRadioChange = (value: any) => {
    id_type.value = value;
};
const defaultIdType = ref(id_type.value);
if (!id_type.value) {
    defaultIdType.value = 'zimbabwean-national-id-number';
}
</script>

<template>
    <Head :title="$t('trans.create_staff')" />
    <PageContainer :breadcrumbs="breadcrumbs">
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
                <EmailAddress
                    v-model="email"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'email')"
                    :error="form.errors.email"
                />
                <DateOfBirth
                    v-model="date_of_birth"
                    :is-required="true"
                    :label-uppercase="true"
                    :teleport="true"
                    :error="form.errors.date_of_birth"
                    @update:model-value="clearFormErrors(form, 'date_of_birth')"
                />
            </div>
        </BaseCard>
        <div class="my-8 grid grid-cols-1 gap-3 md:grid-cols-2">
            <BaseCard :title="$t('trans.identity')" :description="$t('trans.identity_description')">
                <div class="mb-3 flex flex-col">
                    <HeadingSmall :title="$t('trans.id_type')" :description="$t('trans.id_type_description')" class="my-5" />
                    <BaseRadioGroup
                        :options="idTypes"
                        :default-value="defaultIdType"
                        :label-uppercase="true"
                        :is-required="true"
                        @update:modelValue="onRadioChange"
                    />
                </div>
                <div class="grid-col-1 mt-4 grid gap-3 md:grid-cols-2">
                    <template v-if="id_type === 'zimbabwean-national-id-number'">
                        <IdNumber
                            v-model="id_number"
                            :is-required="true"
                            @input="clearFormErrors(form, 'id_number')"
                            :error="form.errors.id_number"
                        />
                    </template>
                    <template v-else>
                        <PassportNumber
                            v-model="passport_number"
                            :is-required="true"
                            @input="clearFormErrors(form, 'passport_number')"
                            :error="form.errors.passport_number"
                        />
                        <CountryComboSelect :form="form" v-model="country" :error="form.errors.country" :label-uppercase="true" :is-required="true" />
                    </template>
                </div>
            </BaseCard>
            <BaseCard :title="$tChoice('trans.role', 2)" :description="$t('trans.role_details_description')">
                <div class="mt-4 grid grid-cols-1">
                    <RoleSelect :label-uppercase="true" :is-multi="true" :is-searchable="true" v-model="role_ids" />
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
    </PageContainer>
</template>
