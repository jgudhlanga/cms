import { StudentPaymentReceipt } from "@/types/finance";
import { ref } from "vue";
import { errorAlert } from '@/lib/alerts';
import { trans } from 'laravel-vue-i18n';
import HttpService from '@/services/http.service';

export const useStudentsFinancials = () => {
    const isLoading = ref(false);
    const studentPaymentReceipts = ref<StudentPaymentReceipt[]>([]);

    const getStudentFinancials = async (studentId: string) => {
        try {
            isLoading.value = true;
            const response = await HttpService.get(route('v1.financials.student.receipts', { student: studentId }));
            studentPaymentReceipts.value = response.data;
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('finance.receipts') }));
        } finally {
            isLoading.value = false;
        }
    };
    return {
        getStudentFinancials,
        studentPaymentReceipts,
        isLoading,
    };
};