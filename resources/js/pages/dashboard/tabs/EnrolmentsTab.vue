<script setup lang="ts">
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName, icons } from '@/lib/icons';
import { DailyDistribution, DepartmentDistribution, EnrolmentSummary, LevelDistribution } from '@/types/dasboard';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Chart, registerables } from 'chart.js';
import { trans } from 'laravel-vue-i18n';
import { Check, Clock, FileText, ListChecks, UserPlus, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

Chart.register(...registerables);

interface Props {
    departmentDistribution: DepartmentDistribution[];
    levelDistribution: LevelDistribution[];
    dailyDistribution: DailyDistribution[];
    enrolmentSummary: EnrolmentSummary;
    intakePeriods: IntakePeriod[];
    handleFilterChange: (option: SelectOption) => void;
}

const props = defineProps<Props>();
const intakePeriodModel = defineModel<SelectOption | null>('intakePeriodModel');

const applicationsTitle = computed(() => {
    const intakeLabel = intakePeriodModel.value?.label;
    if (!intakeLabel) {
        return trans('dashboard.applications');
    }

    return trans('dashboard.applications_for_intake', { intake: intakeLabel });
});

const acceptanceRateSubtext = computed(() => {
    const { applications, offersMade } = props.enrolmentSummary;
    if (applications === 0) {
        return trans('dashboard.acceptance_rate', { rate: '0' });
    }

    const rate = Math.round((offersMade / applications) * 100);

    return trans('dashboard.acceptance_rate', { rate: String(rate) });
});

const yieldRateSubtext = computed(() => {
    const { offersMade, confirmed } = props.enrolmentSummary;
    if (offersMade === 0) {
        return trans('dashboard.yield_rate', { rate: '0' });
    }

    const rate = Math.round((confirmed / offersMade) * 100);

    return trans('dashboard.yield_rate', { rate: String(rate) });
});

const levelChart = ref<HTMLCanvasElement | null>(null);
const enrollmentChart = ref<HTMLCanvasElement | null>(null);

const { generateRandomCode } = useUtils();
const normalizeColor = (value: number) => Math.min(255, Math.max(80, value));

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
    const labels = props.levelDistribution?.map((d) => d.levelName) ?? [];
    const data = props.levelDistribution?.map((d) => d.levelCount) ?? [];
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
    const labels = props.dailyDistribution?.map((d) => d.date) ?? [];
    const data = props.dailyDistribution?.map((d) => d.count) ?? [];
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

let levelChartInstance: Chart | null = null;
let enrollmentChartInstance: Chart | null = null;

const initCharts = () => {
    if (levelChart.value) {
        if (levelChartInstance) levelChartInstance.destroy();
        levelChartInstance = new Chart(levelChart.value, {
            type: 'doughnut',
            data: { ...levelChartData.value },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'left', align: 'center' } },
                cutout: '60%',
            },
        });
    }

    if (enrollmentChart.value) {
        if (enrollmentChartInstance) enrollmentChartInstance.destroy();
        enrollmentChartInstance = new Chart(enrollmentChart.value, {
            type: 'line',
            data: { ...enrollmentData.value },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false } },
                    x: { grid: { display: false } },
                },
            },
        });
    }
};

onMounted(() => {
    initCharts();
});

watch(
    () => [props.levelDistribution, props.dailyDistribution],
    () => {
        initCharts();
    },
    { deep: true },
);
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="flex items-center justify-end gap-2">
            <div
                class="flex min-w-0 shrink-0 items-center gap-2 rounded-lg border border-border/60 bg-muted/20 px-3 py-2 sm:min-w-[280px] sm:max-w-md"
            >
                <component :is="icons[IconName.calendar]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $tChoice('trans.intake_period', 1) }}</span>
                <IntakePeriodComboSelect
                    :data="intakePeriods"
                    label=""
                    v-model="intakePeriodModel"
                    :vertical-layout="false"
                    :is-required="true"
                    width-class="w-full"
                    class="min-w-0 flex-1"
                    @update:modelValue="handleFilterChange"
                />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            <MetricCard
                compact
                accent="bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                :title="applicationsTitle"
                :value="enrolmentSummary.applications"
                :subtext="$t('dashboard.total_applications')"
                trend="neutral"
            >
                <template #icon><FileText class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                :title="$t('dashboard.offers_made')"
                :value="enrolmentSummary.offersMade"
                :subtext="acceptanceRateSubtext"
                trend="neutral"
            >
                <template #icon><Check class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                :title="$t('dashboard.confirmed')"
                :value="enrolmentSummary.confirmed"
                :subtext="yieldRateSubtext"
                trend="neutral"
            >
                <template #icon><UserPlus class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                :title="$t('dashboard.waitlisted')"
                :value="enrolmentSummary.waitlisted"
                :subtext="$t('dashboard.waitlisted_applications')"
                trend="neutral"
            >
                <template #icon><ListChecks class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                :title="$t('dashboard.provisional')"
                :value="enrolmentSummary.provisional"
                :subtext="$t('dashboard.provisional_applications')"
                trend="neutral"
            >
                <template #icon><Clock class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                :title="$t('dashboard.failed_rejected')"
                :value="enrolmentSummary.failedRejected"
                :subtext="$t('dashboard.failed_rejected_applications')"
                trend="down"
            >
                <template #icon><XCircle class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="flex flex-col gap-4">
            <DistributionByDepartment
                :department-distribution="departmentDistribution"
                :show-actions-column="true"
                :show-filters="false"
                v-model:intakePeriodModel="intakePeriodModel"
            />
            <DashboardCard :title="$t('dashboard.distribution_by_level')">
                <div class="mt-2 h-64">
                    <canvas ref="levelChart"></canvas>
                </div>
            </DashboardCard>
            <DashboardCard :title="$t('dashboard.daily_applications')">
                <div class="mt-2 h-64">
                    <canvas ref="enrollmentChart"></canvas>
                </div>
            </DashboardCard>
        </div>

        <DashboardCard :title="$t('dashboard.retention_rate')">
            <div
                class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
            >
                {{ $t('dashboard.line_chart_placeholder') }}
            </div>
        </DashboardCard>
    </div>
</template>
