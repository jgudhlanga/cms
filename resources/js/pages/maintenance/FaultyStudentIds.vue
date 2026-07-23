<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useFaultyStudentIds } from '@/composables/maintenance/useFaultyStudentIds';
import { useFaultyStudentIdSelection } from '@/composables/maintenance/useFaultyStudentIdSelection';
import { openFaultyStudentMergeSuccessDialog } from '@/composables/maintenance/useFaultyStudentMergeSuccessDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
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

const {
    createFaultyStudentIdColumns,
    fetchFaultyStudentIds,
    syncDraftIdNumbers,
    handleBulkFix,
    isLoading,
    isBulkFixing,
} = useFaultyStudentIds();

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

const visibleStudents = computed(() => students.value.data);

const { selectedStudentIds, selectAllModel, selectedCount, clearSelection, pruneSelectionToVisibleStudents } =
    useFaultyStudentIdSelection(visibleStudents);

const showMergeSuccessDialog = (result: FaultyStudentMergeResult | null | undefined): void => {
    if (result == null || mergeResultShown.value) {
        return;
    }

    mergeResultShown.value = true;
    openFaultyStudentMergeSuccessDialog(result);
};

const applyStudentsResponse = (response: Awaited<ReturnType<typeof fetchFaultyStudentIds>>): void => {
    if (!response) {
        return;
    }

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
    pruneSelectionToVisibleStudents();
};

const loadStudents = async (nextFilters: FaultyStudentIdsFiltersState = {}) => {
    filters.value = nextFilters;
    applyStudentsResponse(await fetchFaultyStudentIds(nextFilters));
};

const loadStudentsFromUrl = async (url: string) => {
    applyStudentsResponse(await fetchFaultyStudentIds(filters.value, url));
};

const recoverEmptyPage = async (): Promise<void> => {
    const currentPage = students.value.meta.current_page || 1;
    const lastPage = students.value.meta.last_page || 1;
    const isEmptyPage = students.value.data.length === 0;

    if (!isEmptyPage || currentPage <= 1 || lastPage < 1) {
        return;
    }

    const targetPage = Math.min(currentPage - 1, lastPage);
    const path = students.value.meta.path;

    if (!path || targetPage < 1) {
        await loadStudents(filters.value);
        return;
    }

    const url = new URL(path, window.location.origin);
    url.searchParams.set('page', String(targetPage));

    if (filters.value.search) {
        url.searchParams.set('search', filters.value.search);
    }

    await loadStudentsFromUrl(url.pathname + url.search);
};

const removeFixedStudents = async (ids: number[]): Promise<void> => {
    if (ids.length === 0) {
        return;
    }

    const idSet = new Set(ids);
    const previousCount = students.value.data.length;
    students.value = {
        ...students.value,
        data: students.value.data.filter((student) => !idSet.has(student.id)),
        meta: {
            ...students.value.meta,
            total: Math.max(0, (students.value.meta.total ?? 0) - ids.length),
        },
    };

    draftIdNumbers.value = Object.fromEntries(
        Object.entries(draftIdNumbers.value).filter(([id]) => !idSet.has(Number(id))),
    );
    selectedStudentIds.value = selectedStudentIds.value.filter((id) => !idSet.has(id));

    if (students.value.data.length === 0 && previousCount > 0) {
        await recoverEmptyPage();
    }
};

const columns = computed(() => {
    void selectedStudentIds.value;
    void selectAllModel.value;
    void savingStudentIds.value;

    return createFaultyStudentIdColumns({
        draftIdNumbers,
        savingStudentIds,
        selectedStudentIds,
        selectAllModel,
        onFixed: removeFixedStudents,
    });
});

const onBulkFix = (): void => {
    handleBulkFix(selectedStudentIds.value, async () => {
        clearSelection();
        await loadStudents(filters.value);
    });
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

watch(visibleStudents, () => pruneSelectionToVisibleStudents());
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
                >
                    <template #head-right>
                        <div v-if="selectedCount > 0" class="ml-2 flex items-center gap-3">
                            <BaseButton
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.success"
                                type="button"
                                class="rounded-full"
                                :processing="isBulkFixing"
                                @click="onBulkFix"
                            >
                                {{
                                    trans('trans.maintenance_faulty_data_bulk_fix', {
                                        count: selectedCount,
                                    })
                                }}
                            </BaseButton>
                            <BaseButton
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.shade"
                                type="button"
                                class="rounded-full"
                                :disabled="isBulkFixing"
                                @click="clearSelection"
                            >
                                {{ trans('trans.clear_selection') }}
                            </BaseButton>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </PageContainer>
</template>
