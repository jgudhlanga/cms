<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { Ledger } from '@/types/integrations';

interface Props {
    details?: Ledger|any;
    color: string;
    message?: string;
}

defineProps<Props>();

const { formatDate, formatCurrency } = useUtils();
const currentDate = new Date();
</script>

<template>
    <div class="flex h-screen flex-1 items-center bg-gray-50">
        <div class="mx-auto my-auto w-full overflow-hidden rounded-xl bg-white shadow-lg transition-all duration-300 hover:shadow-xl md:w-1/2">
            <!-- Animated Checkmark -->
            <slot name="header" />
            <!-- Content -->
            <div class="px-6 py-6">
                <!-- Transaction Details -->
                <div class="mb-6 rounded-xl border border-gray-100 bg-gray-50 p-5" v-if="details">
                    <h2 class="mb-3 flex items-center text-lg font-semibold text-gray-700">
                        <BaseIcon :name="IconName.receipt" size="18" :class="`mr-2 text-${color}-600`" />{{ $t('trans.transaction_details') }}
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ $t('trans.reference') }}</span>
                            <span class="font-mono text-gray-800">#{{ details?.attributes?.paymentReference ?? '---' }}</span>
                        </div>
                        <div class="flex justify-between" v-if="details?.attributes?.feeType">
                            <span class="text-gray-600">{{ $tChoice('trans.type', 1) }}</span>
                            <span class="font-mono text-gray-800">{{ details?.attributes?.feeType ?? '---' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ $tChoice('trans.amount', 1) }}</span>
                            <span :class="`font-semibold text-${color}-600`">{{ formatCurrency(String(details?.attributes?.amount)) ?? '---'}}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ $tChoice('trans.payment_option', 1) }}</span>
                            <span class="flex items-center text-gray-800">{{ details?.attributes?.paymentOption ?? '---' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ $t('trans.date') }}</span>
                            <span class="text-gray-800">{{ formatDate(details?.attributes?.paymentDate ?? currentDate) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2">
                            <div class="text-gray-600">{{ $tChoice('trans.status', 1) }}</div>
                            <div :class="`flex items-center rounded-full bg-${color}-100 px-2 py-1 font-medium text-${color}-600 uppercase`">
                                <slot name="status" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <div :class="`mb-6 rounded-lg border border-${color}-100 bg-${color}-50 p-4`" v-if="message">
                    <div :class="`flex items-center justify-center text-${color}-700`">
                        <BaseIcon :name="IconName.info" size="18" :class="`mr-2 text-${color}-600`" />
                        {{ message }}
                    </div>
                </div>

                <!-- Next Steps -->
                <slot name="next-steps" />

                <!-- Action Buttons -->
                <div class="flex flex-col items-center justify-end space-y-3">
                    <slot name="action-buttons" />
                </div>
            </div>
        </div>
    </div>
</template>
