<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
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
    <BaseCard :title="$t('finance.receipt')" color-variant="green-500">
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
    </BaseCard>
</template>
