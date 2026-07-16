<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { hasAbility } from '@/lib/permissions';
import type { DataFilters, DataListProps } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink, router } from '@inertiajs/vue3';
import { computed, h } from 'vue';

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

defineProps<{
    results: DataListProps<ResultRow>;
    filters: DataFilters;
    canImport: boolean;
}>();

const breadcrumbs = computed<Link[]>(() => [{ transChoiceKey: 'examinations.title' }]);

const columns = computed(() => [
    { header: 'Discipline', accessorKey: 'discipline' },
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
    { header: 'Subject', accessorKey: 'subject' },
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
    <Head :title="$tChoice('examinations.title', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="results.data"
            :filters="filters"
            :search-url="route('examinations.index')"
            :pagination="{ ...results.links, ...results.meta }"
            :columns="columns"
            :disable-create="true"
            :show-archived-filter="false"
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
