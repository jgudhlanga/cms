<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import PassportNumber from '@/components/core/form/text/PassportNumber.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { ref } from 'vue';

const {
    id_type,
    id_number,
    passport_number,
    country,
    study_permit_number,
    date_of_birth,
    maritalStatus,
    first_name,
    middle_name,
    last_name,
    title,
    gender,
} = storeToRefs(useCreateApplicationFormStore());

const idTypes = [
    { value: 'zimbabwean-national-id-number', label: 'Zimbabwean ID Number', inputId: 'zimbabwean-national-id-number' },
    { value: 'foreign-passport-number', label: 'Foreign Passport Number', inputId: 'foreign-passport-number' },
];

const onRadioChange = (value: any) => {
    id_type.value = value;
};
defineProps<{ form: InertiaForm<CreateApplicationParams> }>();
const defaultIdType = ref(id_type.value ?? 'zimbabwean-national-id-number');
</script>

<template>
    <BaseCard>
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
        </div>
        <div class="flex flex-col">
            <HeadingSmall :title="$t('trans.id_type')" :description="$t('trans.id_type_description')" class="my-5" />
            <BaseRadioGroup
                :options="idTypes"
                :default-value="defaultIdType"
                :label-uppercase="true"
                :is-required="true"
                @update:modelValue="onRadioChange"
            />
        </div>
        <div class="grid-col-1 mt-4 grid gap-3 md:grid-cols-3">
            <template v-if="id_type === 'zimbabwean-national-id-number'">
                <IdNumber v-model="id_number" :is-required="true" @input="clearFormErrors(form, 'id_number')" :error="form.errors.id_number" />
            </template>
            <template v-else>
                <PassportNumber
                    v-model="passport_number"
                    :is-required="true"
                    @input="clearFormErrors(form, 'passport_number')"
                    :error="form.errors.passport_number"
                />
                <CountryComboSelect :form="form" v-model="country" :error="form.errors.country" :label-uppercase="true" :is-required="true" />
                <BaseInput
                    input-id="study_permit_number"
                    :label="$t('trans.study_permit_number')"
                    v-model="study_permit_number"
                    @input="clearFormErrors(form, 'study_permit_number')"
                    :error="form.errors.study_permit_number"
                    :label-uppercase="true"
                />
            </template>
            <DateOfBirth v-model="date_of_birth" input-id="date_of_birth" :is-required="true" :label-uppercase="true" />
        </div>
    </BaseCard>
</template>
