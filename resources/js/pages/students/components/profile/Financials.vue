<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
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
import { Link, useForm } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

type FeeSummary = {
    expectedTotal: number;
    paidFromBank?: number;
    paidFromLedger?: number;
    paidTotal: number;
    outstanding: number;
    hasStudentNumber: boolean;
};

interface Props {
    student: Student;
    feeSummary?: FeeSummary | null;
    payRoute?: string | null;
}

const props = defineProps<Props>();

const {
    fetchStudentLedger,
    ledgerEntries,
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
const canPayOutstanding = computed(
    () => !!props.payRoute && !!props.feeSummary?.hasStudentNumber && Number(props.feeSummary?.outstanding ?? 0) > 0,
);

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
    <div class="flex flex-col gap-4 py-4">
        <div v-if="feeSummary" class="rounded-lg border p-3 sm:p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-3">
                    <div>
                        <h4 class="text-sm font-semibold">{{ $t('finance.tuition_summary') }}</h4>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $t('finance.tuition_summary_hint') }}
                        </p>
                    </div>
                    <dl class="grid gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.paid_from_bank') }}</dt>
                            <dd class="font-medium">{{ formatSummaryUsd(String(feeSummary.paidFromBank ?? 0)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.paid_online') }}</dt>
                            <dd class="font-medium">{{ formatSummaryUsd(String(feeSummary.paidFromLedger ?? 0)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.exam_results_paid_total') }}</dt>
                            <dd class="font-medium">{{ formatSummaryUsd(String(feeSummary.paidTotal ?? 0)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ $t('trans.exam_results_outstanding') }}</dt>
                            <dd class="font-medium">{{ formatSummaryUsd(String(feeSummary.outstanding ?? 0)) }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="canPayOutstanding" class="sm:max-w-xs sm:text-right">
                    <Link :href="payRoute ?? '#'" class="block">
                        <BaseButton type="button" :size="ButtonSize.sm" :variant="ColorVariant.primary" class="w-full sm:w-auto">
                            {{ $t('trans.pay_outstanding_tuition') }}
                        </BaseButton>
                    </Link>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ $t('trans.tuition_fee_accounts_disclaimer') }}
                    </p>
                </div>
            </div>
        </div>
        <StudentPaymentLedgerTable
            :receipts="parsedLedgerEntries"
            :is-loading="isLedgerLoading"
            :format-ledger-date="formatLedgerDate"
            :sanitize-receipt-description="sanitizeReceiptDescription"
            :format-ledger-usd-amount="formatLedgerUsdAmount"
            :format-running-balance="formatRunningBalance"
            :is-charge-entry="isChargeEntry"
            @print="exportTransactionStatementPdf"
        />

        <div class="rounded-lg border p-3 sm:p-4">
            <Collapsible v-slot="{ open }" :default-open="false">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <CollapsibleTrigger
                        class="flex min-w-0 flex-1 items-center gap-2 rounded-sm text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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
                        class="w-full shrink-0 sm:w-auto"
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
                    <div>
                        <BaseInput
                            input-id="payment_reference"
                            :label="$t('finance.payment_reference')"
                            :placeholder="$t('finance.payment_reference_placeholder')"
                            :is-required="true"
                            v-model="form.payment_reference"
                            @input="clearFormErrors(form, 'payment_reference')"
                            :error="form.errors.payment_reference"
                            :inputAutoFocus="true"
                        />
                        <p class="text-muted-foreground mt-1.5 text-xs leading-relaxed">
                            {{ $t('finance.payment_reference_hint') }}
                        </p>
                    </div>
                    <BaseInput
                        input-id="query_description"
                        :label="$t('finance.query_description_optional')"
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
