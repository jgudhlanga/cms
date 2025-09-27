<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { IconName } from '@/enums/icons';
import { errorAlert, successAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import { Ledger } from '@/types/integrations';
import { PaymentCheckResponse } from '@/types/tools';
import { Link } from '@/types/ui';
import { computed, ref } from 'vue';
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
const processingUpdate = ref(false);
const checkData = ref<PaymentCheckResponse | null>(null);

const search = ref('');
const ledgers = ref<Ledger[] | null>([]);

const searchLedger = async () => {
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

const composeDetails = computed(() => {
    return {
        attributes: {
            amount: checkData?.value?.amount,
            clientFee: checkData?.value?.clientFee,
            createdDate: checkData?.value?.createdDate,
            currency: checkData?.value?.currency,
            itemName: checkData?.value?.itemName,
            merchantFee: checkData?.value?.merchantFee,
            merchantId: checkData?.value?.merchantId,
            orderReference: checkData?.value?.orderReference,
            paymentReference: checkData?.value?.reference,
            paymentStatus: checkData?.value?.status,
            paymentOption: checkData?.value?.paymentOption,
            resultUrl: checkData?.value?.resultUrl,
            returnUrl: checkData?.value?.returnUrl,
        },
    };
});

const updateLedgers = async () => {
    processingUpdate.value = true;
    try {
        await HttpService.post(route('integrations.payments.update-status'), composeDetails.value?.attributes);
        successAlert('Payments status updated!');
        router.visit(window.location.href, { replace: true });
    } catch (error: any) {
        errorAlert('Error updating ledgers: ' + error);
    } finally {
        processingUpdate.value = false;
    }
};
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
                <div class="mx-auto flex w-2/3 mb-3 justify-center">
                    <HeadingSmall title="Found Invoices / Payments" />
                </div>
                <LedgerList :ledgers="ledgers" />
            </div>
        </div>
    </PageContainer>
</template>
