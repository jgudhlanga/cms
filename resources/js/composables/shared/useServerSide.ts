import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useServerSide = () => {
    const isLoading = ref(false);
    const getData = async (url: string, getName: () => string) => {
        try {
            isLoading.value = true;
            return await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: getName() }));
        } finally {
            isLoading.value = false;
        }
    };
    return {
        getData,
        isLoading,
    };
};
