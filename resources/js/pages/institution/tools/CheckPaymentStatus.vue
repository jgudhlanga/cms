<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import StatusModal from '@/pages/institution/tools/partials/StatusModal.vue';
import HttpService from '@/services/http.service';
import { usePaymentIntegrationStore } from '@/store/institution/usePaymentIntegrationStore';
import { AuthObject } from '@/types/data-pagination';
import { Ledger } from '@/types/integrations';
import { Link } from '@/types/ui';
import { storeToRefs } from 'pinia';
import { onBeforeUnmount, ref, watch } from 'vue';
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
const { search, reload } = storeToRefs(store);
const ledgers = ref<Ledger[] | null>([]);

const searchLedger = async () => {
    if (search.value === '') {
        errorAlert('Please enter a search term');
        return;
    }
    isSearching.value = true;
    try {
        ledgers.value = await HttpService.get(`integrations/payments/ledger-entries/${search.value}`);
    } catch (error: any) {
        const message = error?.response?.data?.message;
        if (message) errorAlert(error?.response?.data?.message);
    } finally {
        isSearching.value = false;
    }
};

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
    <Head title="Payment Debug" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="mx-auto my-5 flex w-full flex-col">
            <div class="mx-auto flex w-2/3 flex-col rounded-2xl px-10 py-3 shadow-md">
                <div class="flex w-full flex-col">
                    <BaseInput
                        classes="w-full p-6"
                        input-id="order_reference"
                        label="Order Reference / Payment Reference / User Email"
                        v-model="search"
                        placeholder="enter order reference / payment reference / user email"
                        :vertical-layout="true"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                </div>
                <div class="mt-6 flex w-full justify-center md:w-auto">
                    <BaseButton @click="searchLedger" type="button" :processing="isSearching">
                        <BaseIcon :name="IconName.search" />
                        Search
                    </BaseButton>
                </div>
            </div>
            <div v-if="ledgers && ledgers.length > 0 && !isSearching" class="mt-6 flex w-full flex-col">
                <div class="mx-auto mb-3 flex w-2/3 justify-center">
                    <HeadingSmall title="Found Invoices / Payments" />
                </div>
                <LedgerList :ledgers="ledgers" />
            </div>
        </div>
        <StatusModal />
    </PageContainer>
</template>
