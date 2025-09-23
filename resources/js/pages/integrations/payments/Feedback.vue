<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import AnimatedCheckMark from '@/components/core/util/AnimatedCheckMark.vue';
import BasePaymentStatus from '@/components/shared/integraions/BasePaymentStatus.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Ledger } from '@/types/integrations';

interface Props {
    details: Ledger;
}

defineProps<Props>();

const { navigateTo } = useUtils();
</script>

<template>
    <BasePaymentStatus :details="details" color="green" :message="$t('trans.payment_recorded_description')">
        <template #header>
            <div :class="`flex flex-col items-center bg-gradient-to-br from-green-400 to-green-600 px-6 py-8`">
                <AnimatedCheckMark />
                <h1 class="text-2xl font-bold text-green-100">{{ $t('trans.payment_successful') }}!</h1>
                <p :class="`mt-2 text-center text-green-100`">{{ $t('trans.payment_successful_description') }}</p>
            </div>
        </template>
        <template #status>
            <BaseIcon :name="IconName.check_done" size="18" class="mr-2 text-green-600" />
            {{ details.attributes.paymentStatus }}
        </template>
        <template #next-steps>
            <div class="mb-6">
                <h3 class="mb-2 font-medium text-gray-700">{{ $tChoice('trans.next_step', 1) }}</h3>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mr-2 text-green-500">•</span>{{ $t('trans.finish_registration_description') }}</li>
                </ul>
            </div>
        </template>
        <template #action-buttons>
            <BaseButton
                classes="rounded-full"
                :title="$t('trans.finish_registration')"
                @click="navigateTo(route('portal.application.create'))"
                :variant="ColorVariant.success"
            />
        </template>
    </BasePaymentStatus>
</template>
