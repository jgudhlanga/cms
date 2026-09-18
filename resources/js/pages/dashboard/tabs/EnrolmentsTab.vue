<script setup lang="ts">
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { IconName, icons } from '@/lib/icons';
import { DailyDistribution, DepartmentDistribution, EnrolmentSummary, LevelDistribution } from '@/types/dashboard';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Chart, registerables } from 'chart.js';
import { trans } from 'laravel-vue-i18n';
import { Check, Clock, FileText, ListChecks, UserPlus, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';
import { baseAxisOptions, baseTooltip, chartPalette, doughnutOptions } from '../components/chartTheme';

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

const levelChartData = computed(() => {
    const labels = props.levelDistribution?.map((d) => d.levelName) ?? [];
    const data = props.levelDistribution?.map((d) => d.levelCount) ?? [];

    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor: labels.map((_, index) => chartPalette[index % chartPalette.length]),
                borderWidth: 0,
                hoverOffset: 4,
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
                label: trans('dashboard.applications'),
                data,
                backgroundColor: 'rgba(99, 102, 241, 0.12)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointHoverBackgroundColor: 'rgba(99, 102, 241, 1)',
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
            options: doughnutOptions('right'),
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
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: baseTooltip(),
                },
                scales: baseAxisOptions(),
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
    <div class="mt-3 flex flex-col gap-2.5">
        <div class="flex items-center justify-end gap-2">
            <div
                class="flex min-w-0 shrink-0 items-center gap-2 rounded-lg border border-border/60 bg-card px-2.5 py-1.5 shadow-xs sm:min-w-[280px] sm:max-w-md"
            >
                <component :is="icons[IconName.calendar]" class="h-3.5 w-3.5 shrink-0 text-primary" aria-hidden="true" />
                <span
                    class="shrink-0 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase"
                >
                    {{ $tChoice('trans.intake_period', 1) }}
                </span>
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

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <MetricCard
                tone="indigo"
                :title="applicationsTitle"
                :value="enrolmentSummary.applications"
                :subtext="$t('dashboard.total_applications')"
                trend="neutral"
            >
                <template #icon><FileText class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="sky"
                :title="$t('dashboard.offers_made')"
                :value="enrolmentSummary.offersMade"
                :subtext="acceptanceRateSubtext"
                trend="neutral"
            >
                <template #icon><Check class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="emerald"
                :title="$t('dashboard.confirmed')"
                :value="enrolmentSummary.confirmed"
                :subtext="yieldRateSubtext"
                trend="neutral"
            >
                <template #icon><UserPlus class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="amber"
                :title="$t('dashboard.waitlisted')"
                :value="enrolmentSummary.waitlisted"
                :subtext="$t('dashboard.waitlisted_applications')"
                trend="neutral"
            >
                <template #icon><ListChecks class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="violet"
                :title="$t('dashboard.provisional')"
                :value="enrolmentSummary.provisional"
                :subtext="$t('dashboard.provisional_applications')"
                trend="neutral"
            >
                <template #icon><Clock class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="rose"
                :title="$t('dashboard.failed_rejected')"
                :value="enrolmentSummary.failedRejected"
                :subtext="$t('dashboard.failed_rejected_applications')"
                trend="down"
            >
                <template #icon><XCircle class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="flex flex-col gap-2.5">
            <DistributionByDepartment
                :department-distribution="departmentDistribution"
                :show-actions-column="true"
                :show-filters="false"
                origin="dashboard"
                v-model:intakePeriodModel="intakePeriodModel"
            />
            <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
                <DashboardCard :title="$t('dashboard.distribution_by_level')">
                    <div class="h-52">
                        <canvas ref="levelChart"></canvas>
                    </div>
                </DashboardCard>
                <DashboardCard :title="$t('dashboard.daily_applications')">
                    <div class="h-52">
                        <canvas ref="enrollmentChart"></canvas>
                    </div>
                </DashboardCard>
            </div>
        </div>

        <DashboardCard :title="$t('dashboard.retention_rate')">
            <div
                class="flex h-32 w-full items-center justify-center rounded-lg border border-dashed border-border/70 bg-muted/20 text-[11px] text-muted-foreground"
            >
                {{ $t('dashboard.line_chart_placeholder') }}
            </div>
        </DashboardCard>
    </div>
</template>
