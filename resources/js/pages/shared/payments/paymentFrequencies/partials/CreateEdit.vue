<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { usePaymentFrequencies } from '@/composables/shared/usePaymentFrequencies';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { PaymentFrequency, PaymentFrequencyParams } from '@/types/payments';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const { savePaymentFrequency } = usePaymentFrequencies();
const paymentFrequency = ref<PaymentFrequency>();
const form = useForm<PaymentFrequencyParams>({
    title: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    paymentFrequency.value = getModalEdit(APP_MODULE_KEYS.payment_frequencies);
    form.title = paymentFrequency.value?.attributes?.title ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.payment_frequencies"
        :title="`${paymentFrequency ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.payment_frequency', 1)}`"
        :on-form-action="() => savePaymentFrequency(form, paymentFrequency)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
        </template>
    </BaseModal>
</template>
