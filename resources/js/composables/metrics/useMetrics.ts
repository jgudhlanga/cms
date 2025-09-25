import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useMetrics = () => {
    const isLoading = ref(false);

    const loadAdminDashboardMetrics = async (dateRange: any) => {
        try {
            isLoading.value = true;
            return await HttpService.post('api/v1/institution/dashboard/metrics', { date_range: dateRange });
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: 'dashboard metrics' }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        loadAdminDashboardMetrics,
    };
};
