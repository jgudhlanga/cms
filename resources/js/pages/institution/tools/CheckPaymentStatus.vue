<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AnimatedCheckMark from '@/components/core/util/AnimatedCheckMark.vue';
import AnimatedErrorIcon from '@/components/core/util/AnimatedErrorIcon.vue';
import BasePaymentStatus from '@/components/shared/integraions/BasePaymentStatus.vue';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert, successAlert } from '@/lib/alerts';
import { clearFormErrors } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { computed, ref } from 'vue';
import BaseInput from '../../../components/core/form/text/BaseInput.vue';

interface Props {
    auth: AuthObject;
    errors: object;
}

interface PaymentCheckResponse {
    status?: string;
    message?: string;
    amount?: string | number;
    clientFee?: number | string;
    createdDate?: string;
    currency?: string;
    itemName?: string;
    merchantFee?: number | string;
    merchantId?: string;
    orderReference?: string;
    paymentOption?: string;
    reference?: string;
    resultUrl?: string;
    returnUrl?: string;
}

const { orderReferenceSchema } = useSharedFormSchema();

defineProps<Props>();

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        transChoiceKeyIndex: 1,
        href: route('institution.index'),
    },
    { title: 'Payments Debug' },
];

const isLoading = ref(false);
const processingUpdate = ref(false);
const checkData = ref<PaymentCheckResponse | null>(null);
const form = useForm<any>({
    order_reference: '',
});
const submitForm = async () => {
    isLoading.value = true;
    try {
        orderReferenceSchema().parse(form);
        checkData.value = await HttpService.post(route('integrations.payments.check-status', { order_reference: form.order_reference }), {});
    } catch (error: any) {
        checkData.value = null;
        form.setError(error.format());
    } finally {
        isLoading.value = false;
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
        <div class="mx-auto my-5 flex w-full">
            <form @submit.prevent="submitForm()" class="mx-auto flex w-2/3 flex-col rounded-2xl p-10 shadow-md">
                <div class="flex w-full flex-col">
                    <BaseInput
                        classes="w-full p-6"
                        input-id="order_reference"
                        label="Order Reference"
                        v-model="form.order_reference"
                        placeholder="enter order reference"
                        :vertical-layout="false"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'order_reference')"
                        :error="form.errors.order_reference"
                    />
                </div>
                <div class="mt-6 flex w-full justify-center md:w-auto">
                    <BaseButton type="submit" :processing="isLoading"> Check Payment Status </BaseButton>
                </div>
            </form>
        </div>
        <template v-if="checkData?.message?.toLocaleLowerCase() === 'transaction not found'">
            <BasePaymentStatus color="red" :message="checkData?.message">
                <template #header>
                    <div :class="`flex flex-col items-center bg-gradient-to-br from-red-400 to-red-600 px-6 py-8`">
                        <AnimatedErrorIcon />
                        <h1 class="text-2xl font-bold text-red-100">{{ checkData?.status }}!</h1>
                    </div>
                </template>
                <template #status>
                    <BaseIcon :name="IconName.circle_x" size="18" class="mr-2 text-red-600" />
                    {{ checkData?.message }}
                </template>

                <template #action-buttons v-if="checkData?.message?.toLocaleLowerCase() !== 'transaction not found'">
                    <BaseButton classes="rounded-full" title="Update Ledger" @click="() => {}" :variant="ColorVariant.danger" />
                </template>
            </BasePaymentStatus>
        </template>
        <template v-else-if="checkData">
            <form @submit.prevent="updateLedgers()">
                <BasePaymentStatus :details="composeDetails" color="green">
                    <template #header>
                        <div :class="`flex flex-col items-center bg-gradient-to-br from-green-400 to-green-600 px-6 py-8`">
                            <AnimatedCheckMark />
                            <h1 class="text-2xl font-bold text-green-100">{{ composeDetails?.attributes?.paymentStatus }}!</h1>
                            <p :class="`mt-2 text-center text-green-100`">Transaction found</p>
                        </div>
                    </template>
                    <template #status>
                        <BaseIcon :name="IconName.check_done" size="18" class="mr-2 text-green-600" />
                        {{ composeDetails?.attributes?.paymentStatus }}
                    </template>
                    <template #action-buttons>
                        <BaseButton
                            :processing="processingUpdate"
                            type="submit"
                            classes="rounded-full"
                            title="Update Student Payment Status"
                            @click="() => {}"
                            :variant="ColorVariant.success"
                        />
                    </template>
                </BasePaymentStatus>
            </form>
        </template>
    </PageContainer>
</template>
