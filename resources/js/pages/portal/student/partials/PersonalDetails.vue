<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';

const { id_type, id_number, passport_number, country, study_permit_number, date_of_birth, maritalStatus } =
    storeToRefs(useCreateApplicationFormStore());

defineProps<{ form: InertiaForm<any> }>();
const idTypes = [
    {value: 'zimbabwean-national-id-number', label: 'Zimbabwean ID Number', inputId: 'zimbabwean-national-id-number'},
    {value: 'foreign-passport-number', label: 'Foreign Passport Number', inputId: 'foreign-passport-number'},
]
</script>

<template>
    <BaseCard>
        <div class="grid grid-cols-4 gap-4">
           <BaseRadioGroup
               :options="idTypes"
               default-value="zimbabwean-national-id-number"
               :label="$t('trans.id_type')"
           />
            <BaseInput
                input-id="id_number"
                :label="$t('trans.id_number')"
                v-model="id_number"
                @input="clearFormErrors(form, 'id_number')"
                :error="form.errors.id_number"
            />
            <BaseInput
                input-id="passport_number"
                :label="$t('trans.passport_number')"
                v-model="passport_number"
                @input="clearFormErrors(form, 'passport_number')"
                :error="form.errors.passport_number"
            />
        </div>
    </BaseCard>
</template>
