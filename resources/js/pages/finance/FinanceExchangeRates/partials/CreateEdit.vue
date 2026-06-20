<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useFinanceExchangeRates } from '@/composables/finance/useFinanceExchangeRates';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { FinanceExchangeRate, FinanceExchangeRateParams } from '@/types/finance';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const exchangeRate = ref<FinanceExchangeRate>();

const form = useForm<FinanceExchangeRateParams>({
    date: '',
    currency_from: '',
    currency_to: '',
    rate: '',
});

const { saveExchangeRate } = useFinanceExchangeRates();

const { modals } = useModalStore();

watch(modals!, () => {
    exchangeRate.value = getModalEdit(APP_MODULE_KEYS.finance_exchange_rates);

    form.date = exchangeRate.value?.attributes?.date ?? '';
    form.currency_from = exchangeRate.value?.attributes?.currencyFrom ?? '';
    form.currency_to = exchangeRate.value?.attributes?.currencyTo ?? '';
    form.rate = exchangeRate.value?.attributes?.rate ?? '';

    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.finance_exchange_rates"
        :title="`${exchangeRate ? $t('trans.create') : $t('trans.create')} ${$tChoice('finance.exchange_rate', 1)}`"
        :on-form-action="() => saveExchangeRate(form, exchangeRate)"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <BaseDatePicker
                    input-id="date"
                    :label="$tChoice('finance.date', 1)"
                    :enable-time-picker="false"
                    v-model="form.date"
                    :is-required="true"
                    :teleport="true"
                    :error="form.errors.date"
                    @update:model-value="clearFormErrors(form, 'date')"
                />

                <BaseInput
                    input-id="currency_from"
                    :label="$t('finance.from')"
                    v-model="form.currency_from"
                    @input="clearFormErrors(form, 'currency_from')"
                    :error="form.errors.currency_from"
                    :label-uppercase="true"
                    :is-required="true"
                />

                <BaseInput
                    input-id="currency_to"
                    :label="$t('finance.to')"
                    v-model="form.currency_to"
                    @input="clearFormErrors(form, 'currency_to')"
                    :error="form.errors.currency_to"
                    :label-uppercase="true"
                    :is-required="true"
                />

                <BaseInput
                    input-id="rate"
                    :label="$tChoice('finance.rate', 1)"
                    v-model="form.rate"
                    @input="clearFormErrors(form, 'rate')"
                    :error="form.errors.rate"
                    :label-uppercase="true"
                    :is-required="true"
                />
            </div>
        </template>
    </BaseModal>
</template>
