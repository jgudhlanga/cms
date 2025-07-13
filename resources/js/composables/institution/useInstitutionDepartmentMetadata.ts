import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useInstitutionDepartmentMetadata = () => {
    const isLoading = ref(false);

    const loadDepartmentMetadata = async (url: string) => {
        try {
            isLoading.value = true;
            return await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        loadDepartmentMetadata,
    };
};
