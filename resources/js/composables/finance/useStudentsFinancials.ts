import {
    FinanceTransactionQuery,
    StudentLedgerResponse,
    StudentLedgerSummary,
    StudentPaymentReceipt,
    StudentPaymentReceiptCollection,
} from '@/types/finance';
import type { DataListProps } from '@/types/data-pagination';
import { computed, ref } from 'vue';
import { errorAlert } from '@/lib/alerts';
import { trans } from 'laravel-vue-i18n';
import HttpService from '@/services/http.service';
import { buildPaginationPageLinks, mergeJsonApiFiltersIntoRequestPath } from '@/lib/json-api';

const emptyPagination = (): DataListProps<StudentPaymentReceipt> => ({
    data: [],
    links: { first: null, last: null, prev: null, next: null },
    meta: {
        total: 0,
        per_page: 15,
        current_page: 1,
        last_page: 1,
        from: null,
        to: null,
        path: null,
        links: [],
    },
});

function mapReceiptCollection(response: StudentPaymentReceiptCollection): DataListProps<StudentPaymentReceipt> {
    const meta = response.meta ?? ({} as StudentPaymentReceiptCollection['meta']);
    const currentPage = meta.current_page ?? 1;
    const lastPage = meta.last_page ?? 1;

    return {
        data: response.data ?? [],
        links: {
            first: response.links?.first ?? null,
            last: response.links?.last ?? null,
            prev: response.links?.prev ?? null,
            next: response.links?.next ?? null,
        },
        meta: {
            current_page: currentPage,
            last_page: lastPage,
            per_page: meta.per_page ?? 15,
            total: meta.total ?? 0,
            from: meta.from ?? null,
            to: meta.to ?? null,
            path: meta.path ?? null,
            links: meta.links ?? buildPaginationPageLinks(currentPage, lastPage),
        },
    };
}

const emptySummary = (): StudentLedgerSummary => ({
    totalInvoiced: '0.00',
    totalPayments: '0.00',
    outstandingBalance: '0.00',
    paidPercent: 0,
});

export const useStudentsFinancials = () => {
    const isLoading = ref(false);
    const isLedgerLoading = ref(false);
    const receiptsList = ref<DataListProps<StudentPaymentReceipt>>(emptyPagination());
    const ledgerEntries = ref<StudentPaymentReceipt[]>([]);
    const ledgerSummary = ref<StudentLedgerSummary>(emptySummary());
    const transactionQueries = ref<FinanceTransactionQuery[]>([]);
    const isTransactionQueriesLoading = ref(false);
    const isTransactionQuerySaving = ref(false);

    const fetchStudentReceipts = async (studentId: string, page = 1) => {
        try {
            isLoading.value = true;
            const response = await HttpService.get(route('v1.financials.student.receipts', { student: studentId }), {
                params: { page },
            });
            receiptsList.value = mapReceiptCollection(response as StudentPaymentReceiptCollection);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('finance.receipts') }));
            receiptsList.value = emptyPagination();
        } finally {
            isLoading.value = false;
        }
    };

    const fetchStudentReceiptsFromUrl = async (url: string) => {
        try {
            isLoading.value = true;
            const path = mergeJsonApiFiltersIntoRequestPath(url, {});
            const response = await HttpService.get(path);
            receiptsList.value = mapReceiptCollection(response as StudentPaymentReceiptCollection);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('finance.receipts') }));
        } finally {
            isLoading.value = false;
        }
    };

    /** @deprecated Use fetchStudentReceipts */
    const getStudentFinancials = async (studentId: string) => fetchStudentReceipts(studentId, 1);

    const fetchStudentLedger = async (studentId: string) => {
        try {
            isLedgerLoading.value = true;
            const response = (await HttpService.get(
                route('v1.financials.student.ledger', { student: studentId }),
            )) as StudentLedgerResponse;
            ledgerEntries.value = response.data ?? [];
            ledgerSummary.value = response.summary ?? emptySummary();
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('finance.ledger') }));
            ledgerEntries.value = [];
            ledgerSummary.value = emptySummary();
        } finally {
            isLedgerLoading.value = false;
        }
    };

    const studentPaymentReceipts = computed(() => receiptsList.value.data);

    const fetchStudentTransactionQueries = async (studentId: string) => {
        try {
            isTransactionQueriesLoading.value = true;
            const response = (await HttpService.get(
                route('v1.financials.student.transaction-queries.index', { student: studentId })
            )) as { data?: FinanceTransactionQuery[] };
            transactionQueries.value = response.data ?? [];
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('finance.queries') }));
            transactionQueries.value = [];
        } finally {
            isTransactionQueriesLoading.value = false;
        }
    };

    const submitStudentTransactionQuery = async (
        studentId: string,
        payload: { paymentReference: string; description?: string }
    ): Promise<boolean> => {
        try {
            isTransactionQuerySaving.value = true;

            await HttpService.post(route('v1.financials.student.transaction-queries.store', { student: studentId }), {
                payment_reference: payload.paymentReference,
                ...(payload.description ? { description: payload.description } : {}),
            });
            await fetchStudentTransactionQueries(studentId);

            return true;
        } catch {
            errorAlert(trans('trans.operation_failed'));

            return false;
        } finally {
            isTransactionQuerySaving.value = false;
        }
    };

    return {
        fetchStudentReceipts,
        fetchStudentReceiptsFromUrl,
        fetchStudentLedger,
        getStudentFinancials,
        receiptsList,
        ledgerEntries,
        ledgerSummary,
        transactionQueries,
        studentPaymentReceipts,
        isLoading,
        isLedgerLoading,
        isTransactionQueriesLoading,
        isTransactionQuerySaving,
        fetchStudentTransactionQueries,
        submitStudentTransactionQuery,
    };
};
