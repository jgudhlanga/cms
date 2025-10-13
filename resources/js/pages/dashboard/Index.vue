<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useMetrics } from '@/composables/metrics/useMetrics';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import DetailedViewTable from '@/pages/dashboard/partials/DetailedViewTable.vue';
import StatsCount from '@/pages/dashboard/partials/StatsCount.vue';
import { DashboardMetrics } from '@/types/dasboard';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import { computed, onMounted, ref } from 'vue';

Chart.register(...registerables);

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];
interface Props {
    auth: AuthObject;
    errors: object;
}
defineProps<Props>();

const { isLoading, loadAdminDashboardMetrics } = useMetrics();
const metricsData = ref<DashboardMetrics | null>(null);
const departmentChart = ref<HTMLCanvasElement | null>(null);

const departmentCount = computed(() => metricsData.value?.departmentDistribution?.length ?? 0);
const totalApplications = computed(() => metricsData.value?.departmentDistribution?.reduce((sum, d) => sum + d.applicationCount, 0) ?? 0);
const totalMale = computed(() => metricsData.value?.departmentDistribution?.reduce((sum, d) => sum + Number(d.maleCount ?? 0), 0) ?? 0);
const totalFemale = computed(() => metricsData.value?.departmentDistribution?.reduce((sum, d) => sum + Number(d.femaleCount ?? 0), 0) ?? 0);
const disabledCount = computed(() => metricsData.value?.departmentDistribution?.reduce((sum, d) => sum + Number(d.disabledCount ?? 0), 0) ?? 0);

const colorFromName = (name: string, alpha = 0.7): string => {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const r = (hash >> 16) & 255;
    const g = (hash >> 8) & 255;
    const b = hash & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const departmentChartData = computed(() => {
    const labels = metricsData.value?.departmentDistribution?.map((d) => d.departmentName) ?? [];
    const data = metricsData.value?.departmentDistribution?.map((d) => d.applicationCount) ?? [];
    const backgroundColors = labels.map((name) => colorFromName(name, 0.7));
    const borderColors = backgroundColors.map((c) => c.replace('0.7', '1'));
    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
            },
        ],
    };
});

const departmentTableData = computed(() => {
    const total = totalApplications.value || 0;

    return (
        metricsData.value?.departmentDistribution?.map((d) => {
            const color = colorFromName(d.departmentName, 0.7);
            const percentage = total > 0 ? ((d.applicationCount / total) * 100).toFixed(1) : '0.0';
            return {
                ...d,
                color,
                percentage,
            };
        }) ?? []
    );
});

onMounted(async () => {
    metricsData.value = await loadAdminDashboardMetrics(null);

    if (departmentChart.value) {
        new Chart(departmentChart.value, {
            type: 'doughnut',
            data: { ...departmentChartData.value },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                },
                cutout: '60%',
            },
        });
    }
});
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col">
            <template v-if="isLoading">
                <DataLoadingSpinner message="loading metrics..." />
            </template>
            <template v-else>
                <div class="flex flex-col">
                    <ComponentHeader header-title="Department distribution" description="Stats by department" />
                    <div class="my-6 grid grid-cols-1 gap-5 sm:px-0 md:grid-cols-5">
                        <StatsCount title="Departments Count" :value="Number(departmentCount)" />
                        <StatsCount title="Applications Count" :value="Number(totalApplications)" />
                        <StatsCount title="Males" :value="Number(totalMale)" />
                        <StatsCount title="Females" :value="Number(totalFemale)" />
                        <StatsCount title="Disabled" :value="Number(disabledCount)" />
                    </div>
                    <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 md:grid-cols-2">
                        <DetailedViewTable :department-table-data="departmentTableData" />
                        <div class="rounded-lg bg-white px-4 py-2 shadow">
                            <h3 class="mb-4 text-lg font-medium">Department Distribution</h3>
                            <div class="h-auto">
                                <canvas id="departmentChart" ref="departmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </PageContainer>
</template>
