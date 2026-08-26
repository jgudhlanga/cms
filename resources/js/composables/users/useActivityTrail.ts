import {
    activitySubjectLabel,
    activityTrailFiltersEqual,
    activityTrailHasNarrowingFilters,
    activityTrailSearchParams,
    defaultActivityTrailFilters,
    defaultSearchableActivityTrailFilters,
    type ActivityTrailFiltersState,
} from '@/lib/activityTimeline';
import HttpService from '@/services/http.service';
import ToastService from '@/services/toast.service';
import type { Audit } from '@/types/audit';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type { SelectOption } from '@/types/utils';
import { computed, onMounted, ref, toValue, type MaybeRefOrGetter } from 'vue';

type UseActivityTrailOptions = {
    searchable?: MaybeRefOrGetter<boolean>;
};

export const useActivityTrail = (resolveUrl: (params: URLSearchParams) => string, options: UseActivityTrailOptions = {}) => {
    const searchable = computed(() => Boolean(toValue(options.searchable)));
    const initialFilters = (): ActivityTrailFiltersState =>
        searchable.value ? defaultSearchableActivityTrailFilters() : defaultActivityTrailFilters();

    const isLoading = ref(true);
    const activities = ref<Audit[]>([]);
    const page = ref(1);
    const hasMore = ref(false);
    const filters = ref<ActivityTrailFiltersState>(initialFilters());
    const logNameOptions = ref<SelectOption[]>([]);

    const emptyUsesFilterCopy = computed(() => searchable.value && activityTrailHasNarrowingFilters(filters.value));

    const loadActivities = async (): Promise<void> => {
        isLoading.value = true;

        try {
            const response = (await HttpService.get(resolveUrl(activityTrailSearchParams(filters.value, page.value)))) as ApiFilterResponse;
            const nextPage = (response.data ?? []) as Audit[];

            activities.value = page.value === 1 ? nextPage : [...activities.value, ...nextPage];
            hasMore.value = Boolean(response.links?.next);

            if (Array.isArray(response.log_names)) {
                logNameOptions.value = response.log_names.map((name) => ({
                    value: name,
                    label: activitySubjectLabel(name) || name,
                }));
            }
        } catch {
            ToastService.error('Failed to load activity log.');
        } finally {
            isLoading.value = false;
        }
    };

    const resetAndLoad = async (): Promise<void> => {
        filters.value = initialFilters();
        page.value = 1;
        activities.value = [];
        await loadActivities();
    };

    const applyFilters = async (next: ActivityTrailFiltersState): Promise<void> => {
        if (activityTrailFiltersEqual(filters.value, next)) {
            return;
        }

        filters.value = { ...next };
        page.value = 1;
        activities.value = [];
        await loadActivities();
    };

    const loadMore = async (): Promise<void> => {
        page.value += 1;
        await loadActivities();
    };

    onMounted(async () => {
        page.value = 1;
        await loadActivities();
    });

    return {
        activities,
        emptyUsesFilterCopy,
        filters,
        hasMore,
        isLoading,
        logNameOptions,
        applyFilters,
        loadMore,
        resetAndLoad,
        searchable,
    };
};
