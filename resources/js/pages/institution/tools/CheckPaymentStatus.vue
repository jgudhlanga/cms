<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { BaseButton } from '@/components/core/button';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import StatusModal from '@/pages/institution/tools/partials/StatusModal.vue';
import HttpService from '@/services/http.service';
import { usePaymentIntegrationStore } from '@/store/institution/usePaymentIntegrationStore';
import { AuthObject } from '@/types/data-pagination';
import { Ledger, LedgerEmailSearchTypeOption, LedgerEmailSearchTypeSelectionResponse } from '@/types/integrations';
import { RadioGroupOption } from '@/types/forms';
import { Link } from '@/types/ui';
import { storeToRefs } from 'pinia';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import BaseInput from '../../../components/core/form/text/BaseInput.vue';
import LedgerList from './partials/LedgerList.vue';

interface Props {
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        transChoiceKeyIndex: 1,
        href: route('institution.index'),
    },
    { title: 'Payments Debug' },
];

const isSearching = ref(false);
const store = usePaymentIntegrationStore();
const { search, reload, selectedLedgerableType } = storeToRefs(store);
const ledgers = ref<Ledger[] | null>([]);
const availableTypes = ref<LedgerEmailSearchTypeOption[]>([]);
const requiresTypeSelection = ref(false);

const typeOptions = computed<RadioGroupOption[]>(() =>
    availableTypes.value.map((type) => ({
        inputId: `ledgerable-type-${type.value}`,
        value: type.value,
        label: type.label,
    })),
);

const isTypeSelectionResponse = (
    response: Ledger[] | LedgerEmailSearchTypeSelectionResponse,
): response is LedgerEmailSearchTypeSelectionResponse => {
    return 'requiresTypeSelection' in response && response.requiresTypeSelection === true;
};

const buildSearchUrl = () => {
    const encodedSearch = encodeURIComponent(search.value);
    const params = selectedLedgerableType.value ? `?ledgerableType=${selectedLedgerableType.value}` : '';

    return `integrations/payments/ledger-entries/${encodedSearch}${params}`;
};

const searchLedger = async () => {
    if (search.value === '') {
        errorAlert('Please enter a search term');
        return;
    }

    if (requiresTypeSelection.value && !selectedLedgerableType.value) {
        errorAlert('Please select a ledger type');
        return;
    }

    isSearching.value = true;

    try {
        const response = await HttpService.get(buildSearchUrl());

        if (isTypeSelectionResponse(response)) {
            availableTypes.value = response.types;
            requiresTypeSelection.value = true;
            ledgers.value = [];
            selectedLedgerableType.value = '';
            return;
        }

        requiresTypeSelection.value = false;
        availableTypes.value = [];
        ledgers.value = response;
    } catch (error: any) {
        const message = error?.response?.data?.message;
        if (message) errorAlert(error?.response?.data?.message);
    } finally {
        isSearching.value = false;
    }
};

watch(search, () => {
    requiresTypeSelection.value = false;
    availableTypes.value = [];
    selectedLedgerableType.value = '';
    ledgers.value = [];
});

watch(reload, async (newVal) => {
    if (newVal) {
        await searchLedger();
        reload.value = false;
    }
});

onBeforeUnmount(() => {
    store.$reset();
    store.$dispose();
});
</script>

<template>
    <Head :title="$t('trans.ui_payment_debug')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="mx-auto my-5 flex w-full flex-col">
            <div class="mx-auto flex w-2/3 flex-col rounded-2xl px-10 py-3 shadow-md">
                <div class="flex w-full flex-col">
                    <BaseInput
                        classes="w-full p-6"
                        input-id="order_reference"
                        :label="$t('trans.ui_order_reference_payment_reference_user_email')"
                        v-model="search"
                        :placeholder="$t('trans.ui_enter_order_reference_payment_reference_user_email')"
                        :vertical-layout="true"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                </div>
                <div
                    v-if="requiresTypeSelection && typeOptions.length > 0"
                    class="mt-6 flex w-full flex-col"
                >
                    <BaseRadioGroup
                        v-model="selectedLedgerableType"
                        label="Ledger type"
                        :options="typeOptions"
                        :vertical-layout="true"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                </div>
                <div class="mt-6 flex w-full justify-center md:w-auto">
                    <BaseButton @click="searchLedger" type="button" :processing="isSearching">
                        <BaseIcon :name="IconName.search" />
                        {{ $t('trans.ui_search') }}
                    </BaseButton>
                </div>
            </div>
            <div v-if="ledgers && ledgers.length > 0 && !isSearching" class="mt-6 flex w-full flex-col">
                <div class="mx-auto mb-3 flex w-2/3 justify-center">
                    <HeadingSmall :title="$t('trans.ui_found_invoices_payments')" />
                </div>
                <LedgerList :ledgers="ledgers" />
            </div>
        </div>
        <StatusModal />
    </PageContainer>
</template>
