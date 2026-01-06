<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { DailyDistribution, DepartmentDistribution, LevelDistribution } from '@/types/dasboard';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import { computed, onMounted, ref } from 'vue';
import { SelectOption } from '@/types/utils';
import { IntakePeriod } from '@/types/institution';

Chart.register(...registerables);

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];
interface Props {
    auth: AuthObject;
    errors: object;
    departmentDistribution: DepartmentDistribution[];
    levelDistribution: LevelDistribution[];
    dailyDistribution: DailyDistribution[];
    intakePeriods: IntakePeriod[];
    intakePeriod: IntakePeriod;
}
const props = defineProps<Props>();
const { levelDistribution, dailyDistribution } = props;

const levelChart = ref<HTMLCanvasElement | null>(null);
const enrollmentChart = ref<HTMLCanvasElement | null>(null);

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

const enrollmentData = computed(() => {
    const labels = dailyDistribution?.map((d) => d.date) ?? [];
    const data = dailyDistribution?.map((d) => d.count) ?? [];
    return {
        labels,
        datasets: [
            {
                label: 'Applications',
                data,
                backgroundColor: 'rgba(79, 70, 229, 0.2)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
            },
        ],
    };
});

const intakePeriodModel = ref<SelectOption | null>(null);

onMounted(async () => {
    if (props.intakePeriod) {
        intakePeriodModel.value = { value: Number(props.intakePeriod.id), label: props.intakePeriod.attributes.name };
    }
});

const handleFilterChange = (option: SelectOption) => {
    router.get(
        window.location.pathname,
        {
            intake_period_id: String(option.value),
        },
        {
            // options here
        },
    );
};
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

    new Chart(enrollmentChart.value!, {
        type: 'line',
        data: { ...enrollmentData.value },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });
});
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col">
            <div class="flex flex-col">
                <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 md:grid-cols-1">
                    <DistributionByDepartment
                        :department-distribution="departmentDistribution"
                        :show-actions-column="true"
                        :show-filters="true"
                        v-model:intakePeriodModel="intakePeriodModel"
                        :intake-periods="intakePeriods"
                        :handle-filter-change="handleFilterChange"
                    />
                    <div class="gap-6 rounded-lg bg-white px-4 py-2 shadow">
                        <HeadingSmall class="mb-2" title="Distribution by Level" />
                        <div class="h-75">
                            <canvas id="levelChart" ref="levelChart"></canvas>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-6 px-4 sm:px-0">
                        <div class="rounded-lg bg-white px-4 py-2 shadow">
                            <HeadingSmall class="mb-2" title="Daily Distribution" />
                            <div class="h-80">
                                <canvas id="enrollmentChart" ref="enrollmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
