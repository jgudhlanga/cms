<script setup lang="ts">
import StudentPaymentLedgerCardList from '@/components/finance/StudentPaymentLedgerCardList.vue';
import {
    useParsedStudentPaymentReceipts,
    useStudentPaymentReceiptPresentation,
} from '@/composables/finance/useStudentPaymentReceiptPresentation';
import { useStudentsFinancials } from '@/composables/finance/useStudentsFinancials';
import { Enrolment } from '@/types/enrolments';
import { computed, onMounted } from 'vue';

interface Props {
    studentId: string;
    enrolment: Enrolment;
}

const props = defineProps<Props>();

const { fetchStudentReceipts, isLoading, studentPaymentReceipts } = useStudentsFinancials();

const receiptContext = computed(() => ({
    studentName: String(props.enrolment?.attributes?.studentName || ''),
    studentNumber: String(props.enrolment?.attributes?.studentNumber || ''),
}));

const {
    formatMoney,
    formatReceiptDate,
    sanitizeReceiptDescription,
    originalAmountNearReference,
    isUsdAmount,
    receiptReference,
} = useStudentPaymentReceiptPresentation(receiptContext);

const parsedReceipts = useParsedStudentPaymentReceipts(studentPaymentReceipts, receiptContext);

onMounted(async () => {
    await fetchStudentReceipts(props.studentId);
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <h3 class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
            {{ $t('finance.receipt') }}
        </h3>
        <StudentPaymentLedgerCardList
            :receipts="parsedReceipts"
            :is-loading="isLoading"
            :format-receipt-date="formatReceiptDate"
            :sanitize-receipt-description="sanitizeReceiptDescription"
            :receipt-reference="receiptReference"
            :format-money="formatMoney"
            :original-amount-near-reference="originalAmountNearReference"
            :is-usd-amount="isUsdAmount"
        />
    </div>
</template>
