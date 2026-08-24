<script setup lang="ts">
import ExaminationDashboardPanel from '@/components/examinations/ExaminationDashboardPanel.vue';
import type {
    ExaminationChartLabels,
    ExaminationComparison,
    ExaminationDashboardFiltersState,
    ExaminationFilterOptions,
    ExaminationStatusCounts,
    ExaminationStatusLabels,
} from '@/types/examinations';
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
    extraQuery?: Record<string, string | number | undefined | null>;
}>();

const reloadUrl = computed(() => route('dashboard'));
</script>

<template>
    <div class="mt-4">
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
            :reload-url="reloadUrl"
            :extra-query="extraQuery"
        />
    </div>
</template>
