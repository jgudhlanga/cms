<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import IdTypeComboSelect from '@/components/core/form/combobox/IdTypeComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import InputError from '@/components/core/form/InputError.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import PassportNumber from '@/components/core/form/text/PassportNumber.vue';
import { useUtils } from '@/composables/core/useUtils';
import { DISABILITY_OPTIONS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { watch } from 'vue';

const {
    idType,
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
    disability_status,
} = storeToRefs(useCreateApplicationFormStore());

const disabilityOptions = DISABILITY_OPTIONS.filter((option) => option.value !== 'prefer_not_to_say');

const props = withDefaults(
    defineProps<{
        form: InertiaForm<CreateApplicationParams>;
        bare?: boolean;
    }>(),
    {
        bare: false,
    },
);
const { form } = props;
const { isNativeCitizen, formatZimIdNumber } = useUtils();

watch(id_number, (value) => {
    if (!value || !isNativeCitizen(idType.value?.label ?? '')) {
        return;
    }
    const formatted = formatZimIdNumber(value);
    if (formatted && formatted !== value) {
        id_number.value = formatted;
    }
});

const onDisabilityChange = (value: string | boolean | null) => {
    disability_status.value = value;
    clearFormErrors(form, 'disability_status');
};
</script>

<template>
    <component
        :is="bare ? 'div' : BaseCard"
        :class="bare ? 'space-y-6' : undefined"
        :title="bare ? undefined : $t('trans.personal_details')"
        :description="bare ? undefined : $t('trans.personal_details_description')"
    >
        <div :class="bare ? 'grid grid-cols-1 gap-6 sm:grid-cols-2' : 'mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2'">
            <TitleComboSelect :form="form" v-model="title" data-field="title" :error="form.errors.title" :is-required="true" />
            <BaseInput
                input-id="first_name"
                :label="$t('trans.first_name')"
                v-model="first_name"
                :placeholder="$t('trans.ui_enter_firstname')"
                :is-required="true"
                @input="clearFormErrors(form, 'first_name')"
                :error="form.errors.first_name"
            />
            <BaseInput input-id="middle_name" :label="$t('trans.middle_name')" :placeholder="$t('trans.ui_enter_middlename')" v-model="middle_name" />
            <BaseInput
                input-id="last_name"
                :label="$t('trans.last_name')"
                :placeholder="$t('trans.ui_enter_lastname_surname')"
                v-model="last_name"
                :is-required="true"
                @input="clearFormErrors(form, 'last_name')"
                :error="form.errors.last_name"
            />
            <GenderComboSelect :form="form" v-model="gender" data-field="gender" :error="form.errors.gender" :is-required="true" />
            <MaritalStatusComboSelect
                :form="form"
                v-model="maritalStatus"
                data-field="maritalStatus"
                :error="form.errors.maritalStatus"
                :is-required="true"
            />
        </div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <IdTypeComboSelect :form="form" v-model="idType" data-field="idType" :error="form.errors.idType" :is-required="true" />
            <template v-if="isNativeCitizen(idType?.label ?? '')">
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
                <CountryComboSelect :form="form" v-model="country" data-field="country" :error="form.errors.country" :is-required="true" />
                <BaseInput
                    input-id="study_permit_number"
                    :label="$t('trans.study_permit_number')"
                    v-model="study_permit_number"
                    @input="clearFormErrors(form, 'study_permit_number')"
                    :error="form.errors.study_permit_number"
                />
            </template>
        </div>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <DateOfBirth
                v-model="date_of_birth"
                :is-required="true"
                :teleport="true"
                :error="form.errors.date_of_birth"
                @update:model-value="clearFormErrors(form, 'date_of_birth')"
            />
            <div id="disability_status" class="flex w-full flex-col items-start">
                <BaseRadioGroup
                    :label="$t('trans.disability')"
                    v-model="disability_status"
                    class="w-full"
                    :options="disabilityOptions"
                    :error="form.errors.disability_status"
                    orientation="vertical"
                    :vertical-layout="true"
                    mobile-stack
                    @update:modelValue="onDisabilityChange"
                    :is-required="true"
                />
                <InputError :message="form.errors.disability_status" />
            </div>
        </div>
    </component>
</template>
