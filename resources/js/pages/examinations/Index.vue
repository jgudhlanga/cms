<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import ExaminationSearchFilters from '@/components/examinations/filters/ExaminationSearchFilters.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import { hasAbility } from '@/lib/permissions';
import type { DataFilters, DataListProps } from '@/types/data-pagination';
import type {
    ExaminationFilterOptions,
    ExaminationSearchFiltersState,
} from '@/types/examinations';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink, router } from '@inertiajs/vue3';
import { computed, h, ref } from 'vue';

type ResultRow = {
    id: number;
    discipline: string | null;
    courseCode: string | null;
    candidateNumber: string;
    surname: string | null;
    firstNames: string | null;
    subjectCode: string | null;
    subject: string | null;
    grade: string | null;
    session: string | null;
    courseComment: string | null;
};

const props = defineProps<{
    results: DataListProps<ResultRow>;
    filters: ExaminationSearchFiltersState & DataFilters;
    filterOptions: ExaminationFilterOptions;
    canImport: boolean;
}>();

const breadcrumbs = computed<Link[]>(() => [
    { transChoiceKey: 'examinations.title' },
    { transKey: 'examinations.search' },
]);

const activeFilters = ref<ExaminationSearchFiltersState>({
    session: props.filters.session,
    discipline: props.filters.discipline,
    subject_code: props.filters.subject_code,
    surname: props.filters.surname,
    first_names: props.filters.first_names,
    candidate_number: props.filters.candidate_number,
});

const searchUrl = computed(() =>
    mergeQueryParamsIntoRequestPath(route('examinations.index'), {
        session: activeFilters.value.session,
        discipline: activeFilters.value.discipline,
        subject_code: activeFilters.value.subject_code,
        surname: activeFilters.value.surname,
        first_names: activeFilters.value.first_names,
        candidate_number: activeFilters.value.candidate_number,
    }),
);

const applyFilters = (filters: ExaminationSearchFiltersState): void => {
    activeFilters.value = filters;

    router.get(
        route('examinations.index'),
        {
            session: filters.session ?? undefined,
            discipline: filters.discipline ?? undefined,
            subject_code: filters.subject_code ?? undefined,
            surname: filters.surname ?? undefined,
            first_names: filters.first_names ?? undefined,
            candidate_number: filters.candidate_number ?? undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['results', 'filters', 'filterOptions', 'canImport'],
        },
    );
};

const truncateText = (value: string, maxLength = 40): string => {
    if (value.length <= maxLength) {
        return value;
    }

    return `${value.slice(0, maxLength)}…`;
};

const renderTruncatedWithTooltip = (
    value: string | null,
    maxWidthClass = 'max-w-[12rem]',
    maxLength = 40,
) => {
    if (!value) {
        return '---';
    }

    const truncated = truncateText(value, maxLength);

    if (truncated === value) {
        return h('span', { class: `block truncate ${maxWidthClass}` }, value);
    }

    return h(
        BaseTooltip,
        { content: value },
        {
            default: () =>
                h(
                    'span',
                    {
                        class: `block cursor-help truncate ${maxWidthClass} underline decoration-dotted underline-offset-2`,
                    },
                    truncated,
                ),
        },
    );
};

const columns = computed(() => [
    {
        header: 'Discipline',
        accessorKey: 'discipline',
        cell: ({ row }: { row: { original: ResultRow } }) =>
            renderTruncatedWithTooltip(row.original.discipline),
    },
    { header: 'Course Code', accessorKey: 'courseCode' },
    {
        header: 'Candidate_Number',
        accessorKey: 'candidateNumber',
        cell: ({ row }: { row: { original: ResultRow } }) =>
            h(
                InertiaLink,
                {
                    href: route('examinations.candidates.show', row.original.candidateNumber),
                    class: 'text-primary font-medium hover:underline',
                },
                () => row.original.candidateNumber,
            ),
    },
    { header: 'Surname', accessorKey: 'surname' },
    { header: 'First_Names', accessorKey: 'firstNames' },
    { header: 'Subject Code', accessorKey: 'subjectCode' },
    {
        header: 'Subject',
        accessorKey: 'subject',
        cell: ({ row }: { row: { original: ResultRow } }) =>
            renderTruncatedWithTooltip(row.original.subject),
    },
    { header: 'Grade', accessorKey: 'grade' },
    { header: 'Session', accessorKey: 'session' },
    { header: 'Course Comment', accessorKey: 'courseComment' },
]);

const goImport = (): void => {
    router.visit(route('examinations.import'));
};

const goImports = (): void => {
    router.visit(route('examinations.imports.index'));
};
</script>

<template>
    <Head :title="$t('examinations.search')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="mb-4">
            <ExaminationSearchFilters
                :filters="filters"
                :filter-options="filterOptions"
                @change="applyFilters"
            />
        </div>
        <DataTable
            :data="results.data"
            :filters="filters"
            :search-url="searchUrl"
            :pagination="{ ...results.links, ...results.meta }"
            :columns="columns"
            :disable-create="true"
            :show-archived-filter="false"
            :hide-built-in-search="true"
        >
            <template #head-right>
                <div class="flex flex-wrap items-center gap-2">
                    <BaseButton
                        v-if="canImport || hasAbility('import:examinations')"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.sm"
                        :title="$t('examinations.import_history')"
                        @click="goImports"
                    />
                    <BaseButton
                        v-if="canImport || hasAbility('import:examinations')"
                        :variant="ColorVariant.primary"
                        :size="ButtonSize.sm"
                        :title="$t('examinations.import_title')"
                        @click="goImport"
                    />
                </div>
            </template>
        </DataTable>
    </PageContainer>
</template>
