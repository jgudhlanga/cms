<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { BaseInput } from '@/components/core/form';
import RelatiohshipComboSelect from '@/components/core/form/combobox/RelatiohshipComboSelect.vue';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';


const {
    next_of_kin_name,
    next_of_kin_phone_number, relationship, next_of_kin_address_1, next_of_kin_address_2, next_of_kin_address_3, next_of_kin_address_4,
} = storeToRefs(useCreateApplicationFormStore());

const props = defineProps<{ form: InertiaForm<CreateApplicationParams> }>();
const { form } = props;
</script>

<template>
    <BaseCard>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <BaseInput
                input-id="next_of_kin_name"
                :label="$tChoice('trans.name', 1)"
                v-model="next_of_kin_name"
                placeholder="enter next of kin"
                :label-uppercase="true"
                :is-required="true"
                @input="clearFormErrors(form, 'next_of_kin_name')"
                :error="form.errors.next_of_kin_name"
            />
            <BaseInput
                input-id="next_of_kin_phone_number"
                :label="$t('trans.phone_number')"
                v-model="next_of_kin_phone_number"
                placeholder="enter phone number"
                :label-uppercase="true"
                :is-required="true"
                @input="clearFormErrors(form, 'next_of_kin_phone_number')"
                :error="form.errors.next_of_kin_phone_number"
            />
            <RelatiohshipComboSelect
                :form="form"
                v-model="relationship"
                :error="form.errors.relationship"
                :label-uppercase="true"
                :is-required="true"
            />

        </div>
        <div class="flex flex-col">
            <HeadingSmall :title="$t('trans.residential_address')" :description="$t('trans.residential_address_description')" class="my-5" />
        </div>
        <div class="grid-col-1 grid gap-3 md:grid-cols-4">
            <BaseInput
                input-id="next_of_kin_address_1"
                :label="$t('trans.address_1')"
                v-model="next_of_kin_address_1"
                placeholder="enter address line 1 "
                :label-uppercase="true"
                :error="form.errors.next_of_kin_address_1"
                :is-required="true"
            />
            <BaseInput
                input-id="next_of_kin_address_2"
                :label="$t('trans.address_2')"
                v-model="next_of_kin_address_2"
                placeholder="enter address line 2"
                :label-uppercase="true"
                :error="form.errors.next_of_kin_address_2"
                :is-required="true"
            />
            <BaseInput
                input-id="next_of_kin_address_3"
                :label="$t('trans.address_3')"
                v-model="next_of_kin_address_3"
                placeholder="enter address line 3"
                :label-uppercase="true"
                :error="form.errors.next_of_kin_address_3"
                :is-required="true"
            />
            <BaseInput
                input-id="next_of_kin_address_4"
                :label="$t('trans.address_4')"
                v-model="next_of_kin_address_4"
                placeholder="enter address line 4"
                :label-uppercase="true"
                :error="form.errors.next_of_kin_address_4"
            />
        </div>
    </BaseCard>
</template>
