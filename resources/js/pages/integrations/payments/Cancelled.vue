<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import AnimatedErrorIcon from '@/components/core/util/AnimatedErrorIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import Base from '@/pages/integrations/payments/partials/Base.vue';
import { Ledger } from '@/types/integrations';

interface Props {
    details: Ledger;
}

defineProps<Props>();

const { navigateTo } = useUtils();
</script>

<template>
    <Base :details="details" color="red" :message="$t('trans.no_amount_deducted_description')">
        <template #header>
            <div :class="`flex flex-col items-center bg-gradient-to-br from-red-400 to-red-600 px-6 py-8`">
                <AnimatedErrorIcon />
                <h1 class="text-2xl font-bold text-red-100">{{ $t('trans.payment_cancelled') }}!</h1>
                <p :class="`mt-2 text-center text-red-100`">{{ $t('trans.payment_cancelled_description') }}</p>
            </div>
        </template>
        <template #status>
            <BaseIcon :name="IconName.circle_x" size="18" class="mr-2 text-red-600" />
            {{ details.attributes.paymentStatus }}
        </template>
        <template #action-buttons>
            <BaseButton
                classes="rounded-full"
                :title="$t('trans.back')"
                @click="navigateTo(route('portal.application.fee-payment'))"
                :variant="ColorVariant.danger"
            />
        </template>
    </Base>
</template>
