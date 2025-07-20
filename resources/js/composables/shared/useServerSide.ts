import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useServerSide = () => {
    const isLoading = ref(false);
    const getData = async (url: string, name: string) => {
        try {
            isLoading.value = true;
            return await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: name }));
        } finally {
            isLoading.value = false;
        }
    };
    return {
        getData,
        isLoading,
    };
};
