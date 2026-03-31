<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useStudentsFinancials } from '@/composables/finance/useStudentsFinancials';
import { onMounted } from 'vue';

interface Props {
    studentId: string;
}
const props = defineProps<Props>();

const { formatCurrency } = useUtils();
const { getStudentFinancialsByStudentNumber, isLoading, studentPaymentReceipts } = useStudentsFinancials();

onMounted(async () => {
    await getStudentFinancialsByStudentNumber(props.studentId);
});

</script>

<template>
    <BaseCard title="Receipt" description="What the student has paid" color-variant="green-500">
        <div class="flex items-center space-x-3">
            <DataLoadingSpinner v-if="isLoading" />
            <div v-for="studentPaymentReceipt in studentPaymentReceipts" :key="studentPaymentReceipt.id"></div>
        </div>
    </BaseCard>
</template>
