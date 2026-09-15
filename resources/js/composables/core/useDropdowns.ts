import { errorAlert } from '@/lib/alerts';
import { cachedLookup } from '@/lib/dropdownCache';
import HttpService from '@/services/http.service';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

interface DropdownFetchData {
    url: string;
    search?: string;
    transKey?: string;
    transChoiceKey?: string;
}
export const useDropdowns = () => {
    const data = ref<any>(null);
    const page = usePage<PageProps>();

    const fetchData = async ({ url, search, transKey, transChoiceKey }: DropdownFetchData) => {
        try {
            const appendSearchUrl = search ? `${url.includes('?') ? '&' : '?'}search=${encodeURIComponent(search)}` : '';
            const requestUrl = `${url}${appendSearchUrl}`;
            const load = async () => (await HttpService.get(requestUrl)).data;

            // Typed searches rarely repeat, so only the unfiltered lists are shared between comboboxes.
            data.value = search ? await load() : await cachedLookup(`${page.props.auth?.user?.id ?? 'guest'}|${requestUrl}`, load);
        } catch {
            const transValue = transKey ? trans(transKey) : transChoiceKey ? trans_choice(transChoiceKey, 2) : '';
            errorAlert(trans('trans.load_data_failure', { data: transValue }));
        }
    };

    return {
        fetchData,
        data,
    };
};
