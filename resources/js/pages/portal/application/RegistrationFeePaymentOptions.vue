<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { IconButton } from '@/components/core/button';
import AppLogo from '@/components/core/image/AppLogo.vue';
import BaseImage from '@/components/core/image/BaseImage.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import Heading from '@/components/core/util/Heading.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
import { AuthObject } from '@/types/data-pagination';

interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const { paymentMethods } = useDefaults();
const registrationFee = 'USD 20.00';
</script>
<template>
    <nav class="fixed top-0 right-0 left-0 z-50 w-full bg-white px-10 shadow">
        <div class="flex w-full items-center justify-between space-x-5 py-3 md:mx-auto md:w-7/8">
            <div class="flex size-8 items-center justify-start rounded-full border">
                <AppLogo class="shrink-0 rounded-full" />
            </div>
            <Heading :title="user.attributes?.name" />
            <div class="flex">
                <BaseTooltip :content="`${$t('trans.logout')}`">
                    <TextLink :href="route('logout')" method="post" as="button" classes="text-destructive flex items-center">
                        <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" />
                    </TextLink>
                </BaseTooltip>
            </div>
        </div>
    </nav>
    <div class="flex h-screen flex-1 items-center bg-gray-50 py-16">
        <div class="flex h-full w-full flex-col justify-around space-y-6 p-6">
            <div class="mx-auto flex items-center justify-center">
                <BaseAlert :description="$t('trans.registration_fee_payment_description', { amount: registrationFee })" :type="TypeVariant.info" />
            </div>
            <div class="amount">
                <div class="amount-label">{{ $t('trans.amount_to_pay') }}:</div>
                <div class="amount-value">{{ registrationFee }}</div>
            </div>
            <div class="mx-auto flex w-1/3">
                <button id="payButton" class="payment-button">{{ $t('trans.proceed_to_payment') }}</button>
            </div>
            <div class="flex flex-col">
                <div class="text-muted-foreground flex items-center justify-center space-x-3 text-xs font-bold">
                    <span>🔒</span><span>{{ $t('trans.secure_payment_processed_by', { payment_processor: 'Smile N Pay' }) }}</span>
                </div>

                <div class="payment-methods">
                    <BaseImage :src="paymentMethods" classes="rounded-sm h-10" />
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.amount {
    text-align: center;
    margin-bottom: 30px;
}

.amount-label {
    font-size: 16px;
    color: #6c757d;
    margin-bottom: 8px;
    font-weight: 600;
    text-transform: uppercase;
}

.amount-value {
    font-size: 32px;
    font-weight: 700;
    color: #2342f5;
}

.payment-button {
    display: block;
    width: 100%;
    padding: 20px;
    background: linear-gradient(135deg, #2342f5 0%, #00d2ff 100%);
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 18px;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.payment-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(58, 123, 213, 0.35);
}

.payment-button:active {
    transform: translateY(0);
}

.payment-methods {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
}

.payment-method {
    width: 50px;
    height: 30px;
    background-color: #f8f9fa;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
</style>
