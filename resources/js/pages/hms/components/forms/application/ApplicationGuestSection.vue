<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import { clearFormErrors } from '@/lib/forms';
import type { SelectOption } from '@/types/utils';
import type { InertiaForm } from '@inertiajs/vue3';

type ApplicationForm = InertiaForm<{
    applicationType: 'student' | 'guest';
    studentId: number | null;
    studentEnrolmentId: number | null;
    name: string;
    genderId: number | null;
    phoneNumber: string;
    emailAddress: string;
    nextOfKinName: string;
    nextOfKinContact: string;
    checkIn: string;
    checkOut: string;
}>;

defineProps<{
    form: ApplicationForm;
}>();

const gender = defineModel<SelectOption | null>('gender');
</script>

<template>
    <section class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <BaseInput
                input-id="application-guest-name"
                v-model="form.name"
                :label="$tChoice('trans.name', 1)"
                :is-required="true"
                :error="form.errors.name"
                @input="clearFormErrors(form, 'name')"
            />
            <GenderComboSelect
                input-id="application-guest-gender"
                :form="form"
                v-model="gender"
                :is-required="true"
            />
            <BaseInput
                input-id="application-guest-phone"
                v-model="form.phoneNumber"
                :label="$tChoice('trans.phone', 1)"
                :error="form.errors.phoneNumber"
                @input="clearFormErrors(form, 'phoneNumber')"
            />
            <BaseInput
                input-id="application-guest-email"
                v-model="form.emailAddress"
                :label="$t('trans.email')"
                :error="form.errors.emailAddress"
                @input="clearFormErrors(form, 'emailAddress')"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <BaseInput
                input-id="application-next-of-kin-name"
                v-model="form.nextOfKinName"
                :label="$t('hms.next_of_kin_name')"
                :error="form.errors.nextOfKinName"
                :is-required="true"
                @input="clearFormErrors(form, 'nextOfKinName')"
            />
            <BaseInput
                input-id="application-next-of-kin-contact"
                v-model="form.nextOfKinContact"
                :label="$t('hms.next_of_kin_contact')"
                :error="form.errors.nextOfKinContact"
                :is-required="true"
                @input="clearFormErrors(form, 'nextOfKinContact')"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <BaseInput
                input-id="application-check-in"
                v-model="form.checkIn"
                type="date"
                :label="$t('hms.check_in')"
                :error="form.errors.checkIn"
                :is-required="true"
                @input="clearFormErrors(form, 'checkIn')"
            />
            <BaseInput
                input-id="application-check-out"
                v-model="form.checkOut"
                type="date"
                :label="$t('hms.check_out')"
                :error="form.errors.checkOut"
                :is-required="true"
                @input="clearFormErrors(form, 'checkOut')"
            />
        </div>
    </section>
</template>
