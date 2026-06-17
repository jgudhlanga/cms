<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { DailyDistribution, DepartmentDistribution, EnrolmentSummary, LevelDistribution } from '@/types/dasboard';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Chart, registerables } from 'chart.js';
import { trans } from 'laravel-vue-i18n';
import { Check, FileText, ListChecks, UserPlus } from 'lucide-vue-next';
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
    intakePeriodModel: SelectOption | null;
    handleFilterChange: (option: SelectOption) => void;
}

const props = defineProps<Props>();

const applicationsTitle = computed(() => {
    const intakeLabel = props.intakePeriodModel?.label;
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
        <!-- Top Metrics -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard :title="applicationsTitle" :value="enrolmentSummary.applications" :subtext="$t('dashboard.total_applications')" trend="neutral">
                <template #icon><FileText class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard :title="$t('dashboard.offers_made')" :value="enrolmentSummary.offersMade" :subtext="acceptanceRateSubtext" trend="neutral">
                <template #icon><Check class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard :title="$t('dashboard.confirmed')" :value="enrolmentSummary.confirmed" :subtext="yieldRateSubtext" trend="neutral">
                <template #icon><UserPlus class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.waitlisted')"
                :value="enrolmentSummary.waitlisted"
                :subtext="$t('dashboard.waitlisted_applications')"
                trend="neutral"
            >
                <template #icon><ListChecks class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="flex flex-col gap-4">
            <!-- This uses the actual component and real data -->
            <DistributionByDepartment
                :department-distribution="departmentDistribution"
                :show-actions-column="true"
                :show-filters="true"
                :intakePeriodModel="intakePeriodModel"
                @update:intakePeriodModel="$emit('update:intakePeriodModel', $event)"
                :intake-periods="intakePeriods"
                :handle-filter-change="handleFilterChange"
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

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.retention_rate')">
                <div
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    {{ $t('dashboard.line_chart_placeholder') }}
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.withdrawal_and_dropout_reasons')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[48%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">{{ $t('dashboard.reason') }}</th>
                            <th class="w-[16%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">{{ $t('dashboard.count') }}</th>
                            <th class="w-[36%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">{{ $t('dashboard.share') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">{{ $t('dashboard.financial_hardship') }}</td>
                            <td class="py-2 text-gray-900">62</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">42%</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">{{ $t('dashboard.academic_failure') }}</td>
                            <td class="py-2 text-gray-900">38</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">26%</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">{{ $t('dashboard.employment_work') }}</td>
                            <td class="py-2 text-gray-900">24</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-[10px] text-blue-700">16%</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">{{ $t('dashboard.personal_family') }}</td>
                            <td class="py-2 text-gray-900">16</td>
                            <td class="py-2">
                                <span class="inline-block rounded-full bg-purple-100 px-2 py-0.5 text-[10px] text-purple-700">11%</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="truncate py-2 text-gray-900">{{ $t('dashboard.transferred_out') }}</td>
                            <td class="py-2 text-gray-900">8</td>
                            <td class="py-2"><span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700">5%</span></td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.total_withdrawals_this_semester') }}</span>
                        <span class="text-xs font-medium text-gray-900">148</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.withdrawal_rate') }}</span>
                        <span class="text-xs font-medium text-gray-900">2.2%</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.projected_completion_rate') }}</span>
                        <span class="text-xs font-medium text-gray-900">81.4%</span>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
