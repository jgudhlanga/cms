<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import AnimatedErrorIcon from '@/components/core/util/AnimatedErrorIcon.vue';
import BasePaymentStatus from '@/components/shared/integraions/BasePaymentStatus.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { Ledger } from '@/types/integrations';

interface Props {
    details: Ledger;
    redirectRoute: string;
    isApplicationFee?: boolean;
}

defineProps<Props>();

const { navigateTo } = useUtils();
</script>

<template>
    <BasePaymentStatus :details="details" color="amber" :message="$t('trans.no_amount_deducted_description')">
        <template #header>
            <div :class="`flex flex-col items-center bg-gradient-to-br from-amber-400 to-amber-600 px-6 py-8`">
                <AnimatedErrorIcon />
                <h1 class="text-2xl font-bold text-amber-100">{{ $t('trans.payment_failed') }}!</h1>
                <p :class="`mt-2 text-center text-amber-100`">{{ $t('trans.payment_failed_description') }}</p>
            </div>
        </template>
        <template #status>
            <BaseIcon :name="IconName.circle_x" size="18" class="mr-2 text-amber-600" />
            {{ details.attributes.paymentStatus }}
        </template>
        <template #action-buttons>
            <BaseButton
                classes="rounded-full"
                :title="isApplicationFee ? $t('trans.application_fee_payment_try_again') : $t('trans.back')"
                @click="navigateTo(redirectRoute)"
                :variant="ColorVariant.warning"
            />
        </template>
    </BasePaymentStatus>
</template>
