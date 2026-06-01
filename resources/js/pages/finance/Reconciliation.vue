<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import ReconcilePreviewModal from '@/components/finance/ReconcilePreviewModal.vue';
import ReconciliationFilters, { type ReconciliationFiltersState } from '@/components/finance/filters/ReconciliationFilters.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useDataTables } from '@/composables/core/useDataTables';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { ColorVariant } from '@/enums/colors';
import { closeModal, openModal, warningDialog } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import HttpService from '@/services/http.service';
import { h, onMounted, ref } from 'vue';
import type { FinanceTransactionQuery } from '@/types/finance';
import { errorAlert } from '@/lib/alerts';
import { trans, trans_choice } from 'laravel-vue-i18n';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
}>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1, href: route('finance.index') },
    { transChoiceKey: 'finance.reconciliation' },
];

const can = props?.auth?.can;
const { actionButton, tag } = useDataTables();

const queries = ref<DataListProps<FinanceTransactionQuery>>({
    data: [],
    links: { first: null, last: null, prev: null, next: null },
    meta: {
        current_page: 1,
        last_page: 1,
        from: null,
        to: null,
        total: 0,
        per_page: 15,
        path: null,
        links: [],
    },
});
const filters = ref<DataFilters>({});
const isLoading = ref(false);
const activeFilters = ref<ReconciliationFiltersState>({});
const selectedQuery = ref<FinanceTransactionQuery | null>(null);
const isMatchSearching = ref(false);
const isReconcileSubmitting = ref(false);
const matchedStatement = ref<{
    id: number;
    transactionDate: string;
    reference: string;
    narration: string;
    pipe5Details: string;
    amountCredit: string;
    amountDebit: string;
    isoCurrencyCode: string;
} | null>(null);

const withFilters = (url: string): string => {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://localhost';
    const parsed = new URL(url, base);

    if (activeFilters.value.student) {
        parsed.searchParams.set('student', activeFilters.value.student);
    } else {
        parsed.searchParams.delete('student');
    }

    if (activeFilters.value.reference) {
        parsed.searchParams.set('reference', activeFilters.value.reference);
    } else {
        parsed.searchParams.delete('reference');
    }

    if (activeFilters.value.status) {
        parsed.searchParams.set('status', activeFilters.value.status);
    } else {
        parsed.searchParams.delete('status');
    }

    return `${parsed.pathname}${parsed.search}`;
};

const loadQueries = async () => {
    try {
        isLoading.value = true;
        const response = (await HttpService.get(
            withFilters(route('v1.financials.reconciliation.transaction-queries.index'))
        )) as DataListProps<FinanceTransactionQuery>;
        queries.value = response;
        filters.value = { search: null, trashed: 0 };
    } catch {
        errorAlert('Failed to load reconciliation queries.');
    } finally {
        isLoading.value = false;
    }
};

const loadQueriesFromUrl = async (url: string) => {
    try {
        isLoading.value = true;
        const response = (await HttpService.get(withFilters(url))) as DataListProps<FinanceTransactionQuery>;
        queries.value = response;
    } catch {
        errorAlert('Failed to load reconciliation queries.');
    } finally {
        isLoading.value = false;
    }
};

const performReconcile = async () => {
    if (!selectedQuery.value?.id || !matchedStatement.value?.id) {
        return;
    }

    try {
        isReconcileSubmitting.value = true;
        await HttpService.patch(route('v1.financials.reconciliation.transaction-queries.reconcile', {
            transactionQuery: selectedQuery.value.id,
        }), {
            bank_statement_id: matchedStatement.value.id,
        });
        closeModal(APP_MODULE_KEYS.finance_reconcile_preview);
        selectedQuery.value = null;
        matchedStatement.value = null;
        await loadQueries();
    } catch {
        errorAlert('Failed to reconcile transaction query.');
    } finally {
        isReconcileSubmitting.value = false;
    }
};

const confirmReconcile = () => {
    warningDialog(
        () => {
            void performReconcile();
            return true;
        },
        trans('finance.confirm_reconcile_action'),
        trans('trans.warning'),
        trans('finance.reconcile')
    );
};

const performDeclineFromModal = async () => {
    if (!selectedQuery.value?.id) {
        return;
    }

    try {
        isReconcileSubmitting.value = true;
        await HttpService.patch(route('v1.financials.reconciliation.transaction-queries.decline', {
            transactionQuery: selectedQuery.value.id,
        }), {
            reason: 'Declined during reconciliation preview.',
        });
        closeModal(APP_MODULE_KEYS.finance_reconcile_preview);
        selectedQuery.value = null;
        matchedStatement.value = null;
        await loadQueries();
    } catch {
        errorAlert('Failed to decline transaction query.');
    } finally {
        isReconcileSubmitting.value = false;
    }
};

const declineFromModal = () => {
    warningDialog(
        () => {
            void performDeclineFromModal();
            return true;
        },
        trans('finance.confirm_decline_action'),
        trans('trans.warning'),
        trans('finance.decline')
    );
};

const onFilterChange = async (value: ReconciliationFiltersState) => {
    activeFilters.value = value;
    await loadQueries();
};

const openReconcilePreview = async (query: FinanceTransactionQuery) => {
    selectedQuery.value = query;
    matchedStatement.value = null;
    isMatchSearching.value = true;
    openModal({ name: APP_MODULE_KEYS.finance_reconcile_preview });

    try {
        const response = (await HttpService.get(route('v1.financials.reconciliation.transaction-queries.preview-match', {
            transactionQuery: query.id,
        }))) as { data: typeof matchedStatement.value };
        matchedStatement.value = response.data;
    } catch {
        errorAlert('Failed to search bank statement by reference.');
    } finally {
        isMatchSearching.value = false;
    }
};

const closeReconcilePreview = () => {
    closeModal(APP_MODULE_KEYS.finance_reconcile_preview);
    selectedQuery.value = null;
    matchedStatement.value = null;
};

const createReconciliationColumns = () => [
    { header: trans_choice('trans.student', 1), accessorKey: 'attributes.studentName' },
    { header: trans('finance.payment_reference'), accessorKey: 'attributes.paymentReference' },
    {
        header: trans_choice('trans.status', 1),
        accessorKey: 'status',
        cell: ({ row }: { row: { original: FinanceTransactionQuery } }) => {
            const status = row.original.attributes.status;
            const statusLabel = row.original.attributes.statusLabel;
            const variant =
                status === 'reconciled'
                    ? ColorVariant.success
                    : status === 'declined'
                      ? ColorVariant.danger
                      : status === 'under_review'
                        ? ColorVariant.info
                        : ColorVariant.warning;

            return tag(statusLabel, 'rounded-full text-xs px-2 py-1', variant);
        },
    },
    {
        header: trans_choice('trans.action', 2),
        accessorKey: 'actions',
        enableSorting: false,
        meta: { align: 'right' },
        cell: ({ row }: { row: { original: FinanceTransactionQuery } }) => {
            const query = row.original;

            if (query.attributes.status === 'reconciled') {
                return h(
                    'span',
                    { class: 'text-xs text-muted-foreground' },
                    `${trans('finance.reconciled_by')}: ${query.attributes.reconciledByName || '-'}`
                );
            }

            if (query.attributes.status === 'declined') {
                return h(
                    'span',
                    { class: 'text-xs text-muted-foreground' },
                    `${trans('finance.declined_by')}: ${query.attributes.declinedByName || '-'}`
                );
            }

            return h('div', { class: 'flex items-center gap-2 justify-end' }, [
                actionButton({
                    title: trans('finance.reconcile'),
                    variant: ColorVariant.success_outline,
                    classes: 'rounded-full capitalize',
                    onClick: () => openReconcilePreview(query),
                }),
            ]);
        },
    },
];

onMounted(loadQueries);
</script>

<template>
    <Head :title="$t('finance.reconciliation')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('finance.reconciliation')" :description="$t('finance.reconciliation_description')" />
        <BaseAlert
            v-if="!can['view:finances'] && !can['viewAny:finances']"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
        />
        <div v-else class="mt-4 space-y-3">
            <div class="bg-card relative inline-block min-w-full overflow-auto rounded-xl px-6 pt-4 align-middle">
                <ReconciliationFilters :filters="activeFilters" @change="onFilterChange" />
            </div>
            <DataTable
                :data="queries.data"
                :filters="filters"
                :show-archived-filter="false"
                :pagination="{ ...queries.links, ...queries.meta }"
                :columns="createReconciliationColumns()"
                :use-api="true"
                :search-url="route('v1.financials.reconciliation.transaction-queries.index')"
                :api-fetch-action="loadQueriesFromUrl"
                :hide-built-in-search="true"
                :loading="isLoading"
                :show-column-filters="false"
            />
        </div>

        <ReconcilePreviewModal
            :is-match-searching="isMatchSearching"
            :is-reconcile-submitting="isReconcileSubmitting"
            :matched-statement="matchedStatement"
            @confirm-reconcile="confirmReconcile"
            @decline="declineFromModal"
            @close="closeReconcilePreview"
        />
    </PageContainer>
</template>
