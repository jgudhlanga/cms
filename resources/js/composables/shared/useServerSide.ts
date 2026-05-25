import { errorAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath, type MergeQueryIntoUrlOptions } from '@/lib/merge-query-into-url';
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

    const getDataWithMergedQuery = async (
        url: string,
        query: Record<string, unknown>,
        getName: () => string,
        mergeOptions?: MergeQueryIntoUrlOptions,
    ) => {
        const path = mergeQueryParamsIntoRequestPath(url, query, mergeOptions);
        return getData(path, getName);
    };

    return {
        getData,
        getDataWithMergedQuery,
        isLoading,
    };
};
