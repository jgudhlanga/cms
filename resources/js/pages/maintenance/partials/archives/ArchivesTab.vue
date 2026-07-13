<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import {
    mergeMaintenanceArchivesFiltersFromUrl,
    parseMaintenanceArchivesListUrl,
    resolveMaintenanceArchivesListPath,
    useMaintenanceArchives,
} from '@/composables/maintenance/useMaintenanceArchives';
import { TypeVariant } from '@/enums/type-variants';
import { PAGINATION_ITEMS_PER_PAGE } from '@/lib/constants';
import type { ApiFilterResponse, DataListProps } from '@/types/data-pagination';
import type {
    AccountPurgeArchive,
    AccountPurgeArchivePurgeType,
    AccountPurgeArchiveStatus,
    MaintenanceArchivesFiltersState,
} from '@/types/maintenance-archives';
import type { SelectOption } from '@/types/utils';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

const page = usePage();

const { createMaintenanceArchiveColumns, fetchAccountPurgeArchives, isLoading, isRestoring, isFlushing } =
    useMaintenanceArchives();

const archives = ref<DataListProps<AccountPurgeArchive>>({
    data: [],
    links: {
        first: null,
        last: null,
        prev: null,
        next: null,
    },
    meta: {
        total: 0,
        per_page: 0,
        current_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
        path: null,
        links: null,
    },
});

const filters = ref<MaintenanceArchivesFiltersState>({});
const lastListPath = ref<string | null>(null);

const archiveRetentionDays = computed(
    () =>
        archives.value.data[0]?.attributes.archiveRetentionDays ??
        (page.props.purgeArchiveRetentionDays as number | undefined) ??
        30,
);

const purgeTypeOptions = computed<SelectOption[]>(() => [
    { value: 'all', label: trans('trans.maintenance_archives_filter_all_types') },
    { value: 'user_account', label: trans('trans.maintenance_archives_type_user_account') },
    { value: 'student_account', label: trans('trans.maintenance_archives_type_student_account') },
]);

const statusOptions = computed<SelectOption[]>(() => [
    { value: 'all', label: trans('trans.maintenance_archives_filter_all_statuses') },
    { value: 'active', label: trans('trans.maintenance_archives_status_active') },
    { value: 'restored', label: trans('trans.maintenance_archives_status_restored') },
    { value: 'flushed', label: trans('trans.maintenance_archives_status_flushed') },
]);

const purgeTypeSelection = ref<SelectOption | null>(purgeTypeOptions.value[0]);
const statusSelection = ref<SelectOption | null>(statusOptions.value[0]);

const isProcessing = computed(() => isRestoring.value || isFlushing.value);

const syncFilterSelections = () => {
    const purgeType = filters.value.purgeType ?? 'all';
    const status = filters.value.status ?? 'all';

    purgeTypeSelection.value =
        purgeTypeOptions.value.find((option) => option.value === purgeType) ?? purgeTypeOptions.value[0];
    statusSelection.value =
        statusOptions.value.find((option) => option.value === status) ?? statusOptions.value[0];
};

const applyArchivesResponse = (response: ApiFilterResponse, listPath: string): void => {
    archives.value = {
        data: (response.data ?? []) as AccountPurgeArchive[],
        links: response.links ?? archives.value.links,
        meta: response.meta ?? archives.value.meta,
    };
    lastListPath.value = listPath;
    filters.value = parseMaintenanceArchivesListUrl(listPath);
    syncFilterSelections();
};

const loadArchives = async (
    nextFilters: MaintenanceArchivesFiltersState = filters.value,
    pagination?: { page?: number; pageSize?: number },
) => {
    filters.value = nextFilters;

    const pageNumber = pagination?.page ?? (archives.value.meta.current_page || 1);
    const pageSize = pagination?.pageSize ?? (archives.value.meta.per_page || PAGINATION_ITEMS_PER_PAGE);

    const listPath = resolveMaintenanceArchivesListPath(filters.value, undefined, {
        page: pageNumber,
        pageSize,
    });

    const response = await fetchAccountPurgeArchives(filters.value, undefined, {
        page: pageNumber,
        pageSize,
    });

    if (!response) {
        return;
    }

    applyArchivesResponse(response, listPath);

    const currentPage = response.meta?.current_page ?? 1;
    const lastPage = response.meta?.last_page ?? 1;
    const isEmptyPage = (response.data ?? []).length === 0;

    if (isEmptyPage && currentPage > 1 && lastPage >= 1) {
        const targetPage = Math.min(currentPage, lastPage);

        if (targetPage < currentPage) {
            await loadArchives(filters.value, {
                page: targetPage,
                pageSize: response.meta?.per_page ?? PAGINATION_ITEMS_PER_PAGE,
            });
        }
    }
};

const loadArchivesFromUrl = async (url: string) => {
    const mergedFilters = mergeMaintenanceArchivesFiltersFromUrl(filters.value, url);
    const listPath = resolveMaintenanceArchivesListPath(mergedFilters, url);
    const response = await fetchAccountPurgeArchives(mergedFilters, url);

    if (!response) {
        return;
    }

    applyArchivesResponse(response, listPath);

    const currentPage = response.meta?.current_page ?? 1;
    const lastPage = response.meta?.last_page ?? 1;
    const isEmptyPage = (response.data ?? []).length === 0;

    if (isEmptyPage && currentPage > 1 && lastPage >= 1) {
        const targetPage = Math.min(currentPage, lastPage);

        if (targetPage < currentPage) {
            await loadArchives(mergedFilters, {
                page: targetPage,
                pageSize: response.meta?.per_page ?? PAGINATION_ITEMS_PER_PAGE,
            });
        }
    }
};

const reloadArchives = async () => {
    if (lastListPath.value) {
        await loadArchivesFromUrl(lastListPath.value);
        return;
    }

    await loadArchives(filters.value);
};

const columns = computed(() =>
    createMaintenanceArchiveColumns(async () => {
        await reloadArchives();
    }),
);

const onPurgeTypeChange = async (selection: SelectOption | null) => {
    purgeTypeSelection.value = selection;

    const value = selection?.value;

    await loadArchives({
        ...filters.value,
        purgeType:
            value && value !== 'all' ? (value as AccountPurgeArchivePurgeType) : undefined,
    });
};

const onStatusChange = async (selection: SelectOption | null) => {
    statusSelection.value = selection;

    const value = selection?.value;

    await loadArchives({
        ...filters.value,
        status: value && value !== 'all' ? (value as AccountPurgeArchiveStatus) : undefined,
    });
};

onMounted(() => loadArchives());
</script>

<template>
    <div class="space-y-4">
        <BaseAlert :variant="TypeVariant.info">
            {{
                trans('trans.maintenance_archives_retention_notice', {
                    days: archiveRetentionDays,
                })
            }}
        </BaseAlert>

        <DataTable
            :data="archives.data"
            :filters="filters"
            :show-archived-filter="false"
            :pagination="{ ...archives.links, ...archives.meta }"
            :columns="columns"
            :use-api="true"
            :search-url="route('maintenance.account-purge-archives')"
            :api-fetch-action="loadArchivesFromUrl"
            :loading="isLoading"
            :disable-create="true"
            :disable-import="true"
            :disable-export="true"
            :show-column-filters="false"
        >
            <template #head-left>
                <div class="flex flex-wrap items-center gap-2">
                    <BaseCombobox
                        :model-value="purgeTypeSelection"
                        :options="purgeTypeOptions"
                        :placeholder="trans('trans.maintenance_archives_filter_all_types')"
                        class="min-w-48 rounded-full"
                        @update:model-value="onPurgeTypeChange"
                    />
                    <BaseCombobox
                        :model-value="statusSelection"
                        :options="statusOptions"
                        :placeholder="trans('trans.maintenance_archives_filter_all_statuses')"
                        class="min-w-44 rounded-full"
                        @update:model-value="onStatusChange"
                    />
                </div>
            </template>
        </DataTable>

        <Teleport to="body">
            <div
                v-if="isProcessing"
                class="fixed inset-0 z-50 flex items-center justify-center bg-background/70 backdrop-blur-sm"
                role="status"
                aria-live="polite"
                aria-busy="true"
            >
                <DataLoadingSpinner
                    :message="
                        isRestoring
                            ? trans('trans.maintenance_archives_restoring')
                            : trans('trans.maintenance_archives_deleting')
                    "
                />
            </div>
        </Teleport>
    </div>
</template>
