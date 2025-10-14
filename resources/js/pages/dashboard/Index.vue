<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import DistributionByDepartment from '@/pages/dashboard/partials/DistributionByDepartment.vue';
import { DepartmentDistribution, LevelDistribution } from '@/types/dasboard';
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
    departmentDistribution: DepartmentDistribution[];
    levelDistribution: LevelDistribution[];
}
const props = defineProps<Props>();
const { levelDistribution } = props;

const levelChart = ref<HTMLCanvasElement | null>(null);

const { generateRandomCode } = useUtils();

const normalizeColor = (value: number) => Math.min(255, Math.max(80, value)); // keeps it bright

const colorFromLevel = (name: string, alpha = 0.7): string => {
    const randomName = generateRandomCode(name);
    let hash = 0;
    for (let i = 0; i < randomName.length; i++) {
        hash = randomName.charCodeAt(i) + ((hash << 5) - hash);
    }
    const r = normalizeColor((hash >> 16) & 255);
    const g = normalizeColor((hash >> 8) & 255);
    const b = normalizeColor(hash & 255);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const levelChartData = computed(() => {
    const labels = levelDistribution?.map((d) => d.levelName) ?? [];
    const data = levelDistribution?.map((d) => d.levelCount) ?? [];
    const backgroundColors = labels.map((name) => colorFromLevel(name, 0.7));
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

onMounted(async () => {
    if (levelChart.value) {
        new Chart(levelChart.value, {
            type: 'doughnut',
            data: { ...levelChartData.value },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'left',
                        align: 'center',
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
            <div class="flex flex-col">
                <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 md:grid-cols-1">
                    <DistributionByDepartment :department-distribution="departmentDistribution" />
                    <div class="gap-6 rounded-lg bg-white px-4 py-2 shadow">
                        <div class="mb-2 text-lg font-medium">
                            <HeadingSmall title="Distribution by Level" />
                        </div>
                        <div class="h-[300px]">
                            <canvas id="levelChart" ref="levelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
