import { StudentPaymentReceipt } from "@/types/finance";
import { ref } from "vue";
import { errorAlert } from '@/lib/alerts';
import { trans, trans_choice } from 'laravel-vue-i18n';
import HttpService from '@/services/http.service';

export const useStudentsFinancials = () => {
    const isLoading = ref(false);
    const studentPaymentReceipts = ref<StudentPaymentReceipt[]>([]);

    const getStudentFinancialsByStudentNumber = async (studentId: string) => {

        isLoading.value = true;
        //try {
            const response = await HttpService.get(route('v1.financials.student.receipts', { studentId }));
			//studentPaymentReceipts.value = response.data;
		//} 
        //finally {
            isLoading.value = false;
        //}
    };
    return {
        getStudentFinancialsByStudentNumber,
        studentPaymentReceipts,
        isLoading,
    };
};