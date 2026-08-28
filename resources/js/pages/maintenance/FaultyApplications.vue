<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useFaultyApplications } from '@/composables/maintenance/useFaultyApplications';
import { TypeVariant } from '@/enums/type-variants';
import type { DataListProps } from '@/types/data-pagination';
import type { FaultyApplication, FaultyApplicationsFiltersState } from '@/types/faulty-applications';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_faulty_applications' },
];

const { createFaultyApplicationColumns, fetchFaultyApplications, isLoading } = useFaultyApplications();

const applications = ref<DataListProps<FaultyApplication>>({
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

const filters = ref<FaultyApplicationsFiltersState>({});

const columns = computed(() => createFaultyApplicationColumns());

const applyResponse = (response: Awaited<ReturnType<typeof fetchFaultyApplications>>): void => {
    if (!response) {
        return;
    }

    applications.value = {
        data: (response.data ?? []) as FaultyApplication[],
        links: response.links ?? applications.value.links,
        meta: response.meta ?? applications.value.meta,
    };
};

const loadApplications = async (nextFilters: FaultyApplicationsFiltersState = {}): Promise<void> => {
    filters.value = nextFilters;
    applyResponse(await fetchFaultyApplications(nextFilters));
};

const loadApplicationsFromUrl = async (url: string): Promise<void> => {
    applyResponse(await fetchFaultyApplications(filters.value, url));
};

onMounted(() => {
    void loadApplications();
});
</script>

<template>
    <Head :title="trans('trans.maintenance_faulty_applications')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6">
            <BaseAlert
                :type="TypeVariant.info"
                :description="trans('trans.maintenance_faulty_applications_page_description')"
            />

            <div class="min-w-0">
                <DataTable
                    :data="applications.data"
                    :filters="filters"
                    :columns="columns"
                    :pagination="{ ...applications.links, ...applications.meta }"
                    :use-api="true"
                    :search-url="route('maintenance.faulty-applications.data')"
                    :api-fetch-action="loadApplicationsFromUrl"
                    :loading="isLoading"
                    :show-archived-filter="false"
                    :disable-create="true"
                    :disable-import="true"
                    :disable-export="true"
                    :show-column-filters="false"
                />
            </div>
        </div>
    </PageContainer>
</template>
