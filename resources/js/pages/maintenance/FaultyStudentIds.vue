<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useFaultyStudentIds } from '@/composables/maintenance/useFaultyStudentIds';
import { openFaultyStudentMergeSuccessDialog } from '@/composables/maintenance/useFaultyStudentMergeSuccessDialog';
import { TypeVariant } from '@/enums/type-variants';
import type { DataListProps } from '@/types/data-pagination';
import type {
    FaultyStudentIdNumber,
    FaultyStudentIdsFiltersState,
    FaultyStudentMergeResult,
} from '@/types/faulty-student-ids';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    mergeResult?: FaultyStudentMergeResult | null;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_faulty_data' },
];

const { createFaultyStudentIdColumns, fetchFaultyStudentIds, syncDraftIdNumbers, isLoading } =
    useFaultyStudentIds();

const students = ref<DataListProps<FaultyStudentIdNumber>>({
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

const filters = ref<FaultyStudentIdsFiltersState>({});
const draftIdNumbers = ref<Record<number, string>>({});
const savingStudentIds = ref<Set<number>>(new Set());
const mergeResultShown = ref(false);

const showMergeSuccessDialog = (result: FaultyStudentMergeResult | null | undefined): void => {
    if (result == null || mergeResultShown.value) {
        return;
    }

    mergeResultShown.value = true;
    openFaultyStudentMergeSuccessDialog(result);
};

const reloadStudents = async () => {
    await loadStudents(filters.value);
};

const columns = computed(() =>
    createFaultyStudentIdColumns({
        draftIdNumbers,
        savingStudentIds,
        onSaveSuccess: async () => {
            await reloadStudents();
        },
    }),
);

const loadStudents = async (nextFilters: FaultyStudentIdsFiltersState = {}) => {
    filters.value = nextFilters;
    const response = await fetchFaultyStudentIds(nextFilters);

    if (response) {
        const nextStudents = (response.data ?? []) as FaultyStudentIdNumber[];
        students.value = {
            data: nextStudents,
            links: response.links ?? students.value.links,
            meta: response.meta ?? students.value.meta,
        };
        syncDraftIdNumbers(nextStudents, draftIdNumbers);

        const visibleIds = new Set(nextStudents.map((student) => student.id));
        draftIdNumbers.value = Object.fromEntries(
            Object.entries(draftIdNumbers.value).filter(([id]) => visibleIds.has(Number(id))),
        );
    }
};

const loadStudentsFromUrl = async (url: string) => {
    const response = await fetchFaultyStudentIds(filters.value, url);

    if (response) {
        const nextStudents = (response.data ?? []) as FaultyStudentIdNumber[];
        students.value = {
            data: nextStudents,
            links: response.links ?? students.value.links,
            meta: response.meta ?? students.value.meta,
        };
        syncDraftIdNumbers(nextStudents, draftIdNumbers);
    }
};

onMounted(() => {
    loadStudents();
    showMergeSuccessDialog(props.mergeResult);
});

watch(
    () => props.mergeResult,
    (result) => {
        showMergeSuccessDialog(result);
    },
);
</script>

<template>
    <Head :title="trans('trans.maintenance_faulty_data')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6">
            <div class="lg:sticky lg:top-4 lg:self-start">
                <BaseAlert
                    :type="TypeVariant.info"
                    :description="trans('trans.maintenance_faulty_data_page_description')"
                />
            </div>

            <div class="min-w-0">
                <DataTable
                    :data="students.data"
                    :filters="filters"
                    :show-archived-filter="false"
                    :pagination="{ ...students.links, ...students.meta }"
                    :columns="columns"
                    :use-api="true"
                    :search-url="route('maintenance.faulty-student-ids.data')"
                    :api-fetch-action="loadStudentsFromUrl"
                    :loading="isLoading"
                    :disable-create="true"
                    :disable-import="true"
                    :disable-export="true"
                    :show-column-filters="false"
                />
            </div>
        </div>
    </PageContainer>
</template>
