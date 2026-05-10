import { useUtils } from '@/composables/core/useUtils';
import { errorAlert } from '@/lib/alerts';
import { IconName } from '@/lib/icons';
import HttpService from '@/services/http.service';
import Hostels from '@/pages/hms/hostels/components/tabs/Hostels.vue';
import { CustomTab } from '@/types/utils';
import type { HostelFiltersState } from '@/types/hms';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';

export const useHms = () => {
    const { isItTrue } = useUtils();
    const isLoading = ref(false);
    const hmsTabs = (): Array<CustomTab> => {
        return [
            {
                transLabel: () => trans_choice('hms.hostel', 2),
                value: 'hostels',
                component: h(Hostels),
                show: true,
                icon: IconName.hostel,
            },
        ];
    };

    const fetchHostels = async (filters: HostelFiltersState = {}) => {
         try {
            isLoading.value = true;
            return await HttpService.get(route('v1.hms.hostels'), { params: filters });
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    }

    return {
        hmsTabs,
        fetchHostels,
    };
};
