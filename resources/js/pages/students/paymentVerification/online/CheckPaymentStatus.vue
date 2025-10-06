<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseInput } from '@/components/core/form';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { PaymentCheckResponse } from '@/types/tools';
import { ref } from 'vue';

const isChecking = ref(false);
const checkData = ref<PaymentCheckResponse | null>(null);
const search = ref('');
const checkStatus = async () => {
    if (search.value === '') {
        errorAlert('Please enter a search term');
        return;
    }
    isChecking.value = true;
    try {
        const response = await HttpService.post(route('integrations.payments.check-status', search.value), {});
        const message = response?.message;
        if (message) {
            errorAlert(`${message} (${search.value})`);
        } else {
            checkData.value = response;
        }
    } catch (error: any) {
        const message = error?.response?.data?.message;
        if (message) {
            errorAlert(error?.response?.data?.message);
        } else {
            errorAlert('An error occurred while checking payment status. Try again later.');
        }
    } finally {
        isChecking.value = false;
    }
};
</script>

<template>
    <div class="mx-auto my-5 flex w-full flex-col">
        <div class="flex w-2/3 flex-col">
            <div class="flex w-full flex-col">
                <BaseInput
                    classes="w-full p-6"
                    input-id="order_reference"
                    label="Order Reference / Payment Reference / User Email"
                    v-model="search"
                    placeholder="enter order reference / payment reference / user email"
                    :vertical-layout="true"
                    :label-uppercase="false"
                    :is-required="true"
                />
            </div>
            <div class="mt-6 flex w-full md:w-auto">
                <BaseButton @click="checkStatus" type="button" :processing="isChecking">
                    <BaseIcon :name="IconName.search" />
                    Verify Payment with Smile & Pay
                </BaseButton>
            </div>
        </div>
    </div>
</template>
