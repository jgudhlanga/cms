<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import FinancialTotalsProgress from '@/components/finance/FinancialTotalsProgress.vue';
import StudentFinancialSummaryRow from '@/components/finance/StudentFinancialSummaryRow.vue';
import StudentPaymentLedgerTable from '@/components/finance/StudentPaymentLedgerTable.vue';
import {
    useParsedStudentPaymentReceipts,
    useStudentPaymentReceiptPresentation,
} from '@/composables/finance/useStudentPaymentReceiptPresentation';
import { useStudentsFinancials } from '@/composables/finance/useStudentsFinancials';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { closeModal, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import type { Student } from '@/types/students';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const {
    fetchStudentLedger,
    ledgerEntries,
    ledgerSummary,
    isLedgerLoading,
    fetchStudentTransactionQueries,
    submitStudentTransactionQuery,
    transactionQueries,
    isTransactionQueriesLoading,
    isTransactionQuerySaving,
} = useStudentsFinancials();
const form = useForm<{
    payment_reference: string;
    description: string;
}>({
    payment_reference: '',
    description: '',
});

const receiptContext = computed(() => ({
    studentName: props.student?.relationships?.user?.attributes?.name ?? '',
    studentNumber: props.student?.attributes?.studentNumber ?? '',
}));

const {
    formatLedgerDate,
    formatLedgerUsdAmount,
    formatUsdAmount,
    sanitizeReceiptDescription,
    isChargeEntry,
    formatRunningBalance,
} = useStudentPaymentReceiptPresentation(receiptContext);

const parsedLedgerEntries = useParsedStudentPaymentReceipts(
    () => ledgerEntries.value,
    receiptContext,
);

const formatSummaryUsd = formatUsdAmount;

onMounted(async () => {
    if (props.student?.id) {
        await Promise.all([
            fetchStudentLedger(String(props.student.id)),
            fetchStudentTransactionQueries(String(props.student.id)),
        ]);
    }
});

const submitQuery = async (): Promise<void> => {
    if (!props.student?.id || !form.payment_reference.trim()) {
        return;
    }

    const success = await submitStudentTransactionQuery(String(props.student.id), {
        paymentReference: form.payment_reference.trim(),
        description: form.description.trim(),
    });

    if (success) {
        form.payment_reference = '';
        form.description = '';
        form.clearErrors();
        closeModal(APP_MODULE_KEYS.finance_transaction_queries);
    }
};

const onOpenCreateQueryModal = (): void => {
    openModal({ name: APP_MODULE_KEYS.finance_transaction_queries });
};

const exportTransactionStatementPdf = (): void => {
    if (!props.student?.id) {
        return;
    }

    window.open(
        route('documents.transaction-statement', { student: props.student.id }),
        '_blank',
        'noopener,noreferrer',
    );
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="rounded-lg border p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h4 class="text-sm font-semibold">{{ $t('finance.transaction_statement') }}</h4>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ $t('finance.transaction_statement_help') }}
                    </p>
                </div>
                <BaseButton
                    type="button"
                    :size="ButtonSize.sm"
                    :variant="ColorVariant.primary_outline"
                    @click="exportTransactionStatementPdf"
                >
                    {{ $t('finance.export_statement_pdf') }}
                </BaseButton>
            </div>
        </div>
        <StudentFinancialSummaryRow :summary="ledgerSummary" :format-usd="formatSummaryUsd" />
        <FinancialTotalsProgress :summary="ledgerSummary" :format-usd="formatSummaryUsd" />
        <StudentPaymentLedgerTable
            :receipts="parsedLedgerEntries"
            :is-loading="isLedgerLoading"
            :format-ledger-date="formatLedgerDate"
            :sanitize-receipt-description="sanitizeReceiptDescription"
            :format-ledger-usd-amount="formatLedgerUsdAmount"
            :format-running-balance="formatRunningBalance"
            :is-charge-entry="isChargeEntry"
        />

        <div class="rounded-lg border p-4">
            <Collapsible v-slot="{ open }" :default-open="false">
                <div class="flex items-center justify-between gap-3">
                    <CollapsibleTrigger
                        class="flex min-w-0 flex-1 items-center gap-2 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-sm"
                    >
                        <ChevronDown
                            :class="
                                cn(
                                    'size-4 shrink-0 text-muted-foreground transition-transform duration-200',
                                    open && 'rotate-180',
                                )
                            "
                            aria-hidden="true"
                        />
                        <h4 class="text-sm font-semibold truncate">
                            {{ $tChoice('finance.query', 2) }}
                            <span
                                v-if="!isTransactionQueriesLoading && transactionQueries.length"
                                class="font-normal text-muted-foreground"
                            >
                                ({{ transactionQueries.length }})
                            </span>
                        </h4>
                    </CollapsibleTrigger>
                    <BaseButton
                        :size="ButtonSize.sm"
                        :variant="ColorVariant.primary"
                        @click.stop="onOpenCreateQueryModal"
                    >
                        {{ $t('trans.create') }}
                    </BaseButton>
                </div>

                <CollapsibleContent class="pt-3">
                    <p class="text-xs text-muted-foreground">
                        {{ $t('finance.query_missing_transactions') }}.
                        {{ $t('finance.query_missing_transactions_description') }}
                    </p>

                    <div v-if="isTransactionQueriesLoading" class="mt-3 text-xs text-muted-foreground">
                        {{ $t('trans.loading') }}
                    </div>
                    <div v-else-if="!transactionQueries.length" class="mt-3 text-xs text-muted-foreground">
                        {{ $t('finance.no_queries_found') }}
                    </div>
                    <div v-else class="mt-3 space-y-2">
                        <div
                            v-for="query in transactionQueries"
                            :key="query.id"
                            class="rounded-md border px-3 py-2 text-xs"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium">{{ query.attributes.paymentReference }}</span>
                                <span class="rounded bg-muted px-2 py-1">{{ query.attributes.statusLabel }}</span>
                            </div>
                            <p v-if="query.attributes.description" class="mt-1 text-muted-foreground">
                                {{ query.attributes.description }}
                            </p>
                            <p v-if="query.attributes.declineReason" class="mt-1 text-red-600">
                                {{ query.attributes.declineReason }}
                            </p>
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
        <BaseModal
            :name="APP_MODULE_KEYS.finance_transaction_queries"
            :title="$t('finance.query_missing_transactions')"
            :on-form-action="submitQuery"
            :form="form"
            :show-action-button="false"
        >
            <template #body>
                <div class="grid gap-3">
                    <BaseInput
                        input-id="payment_reference"
                        :label="$t('finance.payment_reference')"
                        :is-required="true"
                        v-model="form.payment_reference"
                        @input="clearFormErrors(form, 'payment_reference')"
                        :error="form.errors.payment_reference"
                        :inputAutoFocus="true"
                    />
                    <BaseInput
                        input-id="query_description"
                        :label="$t('trans.description')"
                        v-model="form.description"
                        @input="clearFormErrors(form, 'description')"
                        :error="form.errors.description"
                    />
                </div>
            </template>
            <template #action-button>
                <BaseButton
                    type="submit"
                    :size="ButtonSize.lg"
                    :variant="ColorVariant.primary"
                    :processing="isTransactionQuerySaving"
                    :disabled="!form.payment_reference.trim() || isTransactionQuerySaving"
                >
                    {{ $t('finance.submit_query') }}
                </BaseButton>
            </template>
        </BaseModal>
    </div>
</template>
