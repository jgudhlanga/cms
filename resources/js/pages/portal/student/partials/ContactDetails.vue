<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import Address1 from '@/components/core/form/text/Address1.vue';
import Address2 from '@/components/core/form/text/Address2.vue';
import Address3 from '@/components/core/form/text/Address3.vue';
import Address4 from '@/components/core/form/text/Address4.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import AltPhoneNumber from '@/components/core/form/text/AltPhoneNumber.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';


const {
phone_number,
    alt_phone_number, address_1, address_2, address_3, address_4, email,
} = storeToRefs(useCreateApplicationFormStore());

const props = defineProps<{ form: InertiaForm<CreateApplicationParams> }>();
const { form } = props;
</script>

<template>
    <BaseCard>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <PhoneNumber
                v-model="phone_number"
                placeholder="enter phone number"
                :label-uppercase="true"
                :is-required="true"
                @input="clearFormErrors(form, 'phone_number')"
                :error="form.errors.phone_number"
            />
            <AltPhoneNumber
                v-model="alt_phone_number"
                placeholder="enter alternative phone number "
                :label-uppercase="true"
                @input="clearFormErrors(form, 'alt_phone_number')"
                :error="form.errors.alt_phone_number"
            />
            <EmailAddress
                v-model="email"
                :label-uppercase="true"
                :is-required="true"
                :disabled="true"
            />
        </div>
        <div class="flex flex-col">
            <HeadingSmall :title="$t('trans.residential_address')" :description="$t('trans.residential_address_description')" class="my-5" />
        </div>
        <div class="grid-col-1 grid gap-3 md:grid-cols-4">
            <Address1
                v-model="address_1"
                placeholder="enter address line 1 "
                :label-uppercase="true"
                @input="clearFormErrors(form, 'address_1')"
                :error="form.errors.address_1"
                :is-required="true"
            />
            <Address2
                v-model="address_2"
                placeholder="enter address line 2"
                :label-uppercase="true"
                @input="clearFormErrors(form, 'address_2')"
                :error="form.errors.address_2"
                :is-required="true"
            />
            <Address3
                v-model="address_3"
                placeholder="enter address line 3"
                :label-uppercase="true"
                @input="clearFormErrors(form, 'address_3')"
                :error="form.errors.address_3"
                :is-required="true"
            />
            <Address4
                v-model="address_4"
                placeholder="enter address line 4"
                :label-uppercase="true"
                @input="clearFormErrors(form, 'address_4')"
                :error="form.errors.address_4"
            />
        </div>
    </BaseCard>
</template>
