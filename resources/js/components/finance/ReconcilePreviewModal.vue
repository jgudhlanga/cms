<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { APP_MODULE_KEYS } from '@/lib/constants';

interface MatchedStatement {
    id: number;
    transactionDate: string;
    reference: string;
    narration: string;
    pipe5Details: string;
    amountCredit: string;
    amountDebit: string;
    isoCurrencyCode: string;
}

interface Props {
    isMatchSearching: boolean;
    isReconcileSubmitting: boolean;
    matchedStatement: MatchedStatement | null;
}

defineProps<Props>();

const emit = defineEmits<{
    close: [];
    decline: [];
    confirmReconcile: [];
}>();
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.finance_reconcile_preview"
        :title="$t('finance.reconcile')"
        :has-form="true"
        :show-action-button="false"
        :on-close-modal="() => emit('close')"
    >
        <template #body>
            <p class="text-sm text-muted-foreground">
                {{ $t('finance.reconciliation_impact_warning') }}
            </p>

            <div v-if="isMatchSearching" class="py-8">
                <DataLoadingSpinner class="w-full" />
                <p class="mt-2 text-center text-xs text-muted-foreground">
                    {{ $t('finance.searching_statement_by_reference') }}
                </p>
            </div>

            <div v-else-if="matchedStatement" class="space-y-1 rounded-lg border p-3 text-sm">
                <p>
                    <span class="font-semibold">{{ $t('finance.payment_reference') }}:</span>
                    {{ matchedStatement.reference || '-' }}
                </p>
                <p>
                    <span class="font-semibold">{{ $t('finance.transaction_date') }}:</span>
                    {{ matchedStatement.transactionDate || '-' }}
                </p>
                <p>
                    <span class="font-semibold">{{ $t('finance.amount') }}:</span>
                    {{ matchedStatement.amountCredit || matchedStatement.amountDebit || '-' }}
                    {{ matchedStatement.isoCurrencyCode || '' }}
                </p>
                <p>
                    <span class="font-semibold">{{ $t('finance.description') }}:</span>
                    {{ matchedStatement.narration || '-' }}
                </p>
                <p>
                    <span class="font-semibold">pipe_5_details:</span>
                    {{ matchedStatement.pipe5Details || '-' }}
                </p>
            </div>
            <BaseAlert
                v-else
                :title="$t('finance.no_transactions_found')"
                :description="$t('finance.no_statement_match_found')"
            />
        </template>

        <template #action-button>
            <BaseButton
                type="button"
                :variant="ColorVariant.danger_outline"
                :size="ButtonSize.lg"
                :disabled="isReconcileSubmitting"
                @click="emit('decline')"
            >
                {{ $t('finance.decline') }}
            </BaseButton>
            <BaseButton
                type="button"
                :variant="ColorVariant.success"
                :size="ButtonSize.lg"
                :processing="isReconcileSubmitting"
                :disabled="!matchedStatement || isReconcileSubmitting"
                @click="emit('confirmReconcile')"
            >
                {{ $t('finance.reconcile') }}
            </BaseButton>
        </template>
    </BaseModal>
</template>