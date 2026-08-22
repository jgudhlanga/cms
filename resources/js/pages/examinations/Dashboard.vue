<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import ExaminationDashboardPanel from '@/components/examinations/ExaminationDashboardPanel.vue';
import type {
    ExaminationChartLabels,
    ExaminationComparison,
    ExaminationDashboardFiltersState,
    ExaminationFilterOptions,
    ExaminationStatusCounts,
    ExaminationStatusLabels,
} from '@/types/examinations';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: ExaminationDashboardFiltersState;
    filterOptions: ExaminationFilterOptions;
    statusCounts: ExaminationStatusCounts;
    statusLabels: ExaminationStatusLabels;
    chartLabels: ExaminationChartLabels;
    totalCandidates: number;
    passRate: number | null;
    onlineViewedCount: number;
    onlineViewedRate: number | null;
    comparison: ExaminationComparison | null;
}>();

const breadcrumbs = computed<Link[]>(() => [
    { transChoiceKey: 'examinations.title', href: route('examinations.index') },
    { transKey: 'examinations.dashboard' },
]);
</script>

<template>
    <Head :title="$t('examinations.dashboard')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <ExaminationDashboardPanel
            :filters="filters"
            :filter-options="filterOptions"
            :status-counts="statusCounts"
            :status-labels="statusLabels"
            :chart-labels="chartLabels"
            :total-candidates="totalCandidates"
            :pass-rate="passRate"
            :online-viewed-count="onlineViewedCount"
            :online-viewed-rate="onlineViewedRate"
            :comparison="comparison"
            :reload-url="route('examinations.dashboard')"
        />
    </PageContainer>
</template>
