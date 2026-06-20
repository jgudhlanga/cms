<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import type { OverviewDashboard } from '@/types/dasboard';
import { trans } from 'laravel-vue-i18n';
import {
    AlertTriangle,
    BarChart3,
    Bed,
    BookOpen,
    Building,
    CalendarCheck,
    Coins,
    TrendingDown,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

interface Props {
    overviewDashboard: OverviewDashboard;
}

const props = defineProps<Props>();

const { summary, enrolmentByDepartment, priorityAlerts } = props.overviewDashboard;

const notAvailable = computed(() => trans('dashboard.overview_not_available'));

const formatRate = (value: number | null): string => (value === null ? notAvailable.value : `${value}%`);

const formatCount = (value: number | null): string =>
    value === null ? notAvailable.value : value.toLocaleString();

const metricSubtext = (value: string | null, fallback?: string): string =>
    value ?? fallback ?? notAvailable.value;

const showTrendIcon = (value: string | null): boolean => value !== null;

const hostelValue = computed(() => formatRate(summary.hostelOccupancyRate));

const hostelSubtext = computed(() => {
    if (summary.hostelAvailableBeds === null) {
        return metricSubtext(summary.hostelSubtext);
    }

    return trans('dashboard.overview_hostel_beds_available', { count: summary.hostelAvailableBeds });
});

const atRiskSubtext = computed(() =>
    summary.atRiskStudents === null
        ? metricSubtext(summary.atRiskSubtext)
        : trans('dashboard.overview_at_risk_subtext'),
);

const departmentBarColors = [
    'bg-blue-500',
    'bg-emerald-500',
    'bg-indigo-500',
    'bg-pink-500',
    'bg-orange-500',
    'bg-orange-600',
    'bg-teal-400',
    'bg-indigo-300',
    'bg-gray-400',
];

const alertDotClass = (severity: string): string => {
    if (severity === 'critical') return 'bg-rose-500';
    if (severity === 'warning') return 'bg-amber-500';
    if (severity === 'success') return 'bg-emerald-500';

    return 'bg-blue-500';
};

const formatAlertTime = (updatedAt: string | null): string => {
    if (!updatedAt) {
        return notAvailable.value;
    }

    return new Date(updatedAt).toLocaleString();
};
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
                :title="$t('dashboard.overview_total_students')"
                :value="formatCount(summary.totalStudents)"
                :subtext="metricSubtext(summary.totalStudentsSubtext)"
                :trend="summary.totalStudentsTrend ?? 'neutral'"
            >
                <template #icon><Users class="h-4 w-4" /></template>
                <template v-if="showTrendIcon(summary.totalStudentsTrend)" #trendIcon>
                    <TrendingUp v-if="summary.totalStudentsTrend === 'up'" class="h-3 w-3" />
                    <TrendingDown v-else class="h-3 w-3" />
                </template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.overview_attendance_rate')"
                :value="formatRate(summary.attendanceRate)"
                :subtext="metricSubtext(summary.attendanceSubtext)"
                :trend="summary.attendanceTrend ?? 'neutral'"
            >
                <template #icon><CalendarCheck class="h-4 w-4" /></template>
                <template v-if="showTrendIcon(summary.attendanceTrend)" #trendIcon>
                    <TrendingUp v-if="summary.attendanceTrend === 'up'" class="h-3 w-3" />
                    <TrendingDown v-else class="h-3 w-3" />
                </template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.overview_pass_rate')"
                :value="formatRate(summary.passRate)"
                :subtext="metricSubtext(summary.passRateSubtext)"
                :trend="summary.passRateTrend ?? 'neutral'"
            >
                <template #icon><BarChart3 class="h-4 w-4" /></template>
                <template v-if="showTrendIcon(summary.passRateTrend)" #trendIcon>
                    <TrendingUp v-if="summary.passRateTrend === 'up'" class="h-3 w-3" />
                    <TrendingDown v-else class="h-3 w-3" />
                </template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.overview_fee_collection')"
                :value="formatRate(summary.feeCollectionRate)"
                :subtext="metricSubtext(summary.feeCollectionSubtext)"
                :trend="summary.feeCollectionTrend ?? 'neutral'"
            >
                <template #icon><Coins class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
                :title="$t('dashboard.overview_programmes')"
                :value="summary.programmeCount"
                :subtext="metricSubtext(summary.programmeSubtext)"
                :trend="summary.programmeTrend ?? 'neutral'"
            >
                <template #icon><BookOpen class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.overview_departments')"
                :value="summary.departmentCount"
                :subtext="metricSubtext(summary.departmentSubtext)"
                :trend="summary.departmentTrend ?? 'neutral'"
            >
                <template #icon><Building class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.overview_hostel_occupancy')"
                :value="hostelValue"
                :subtext="hostelSubtext"
                :trend="summary.hostelTrend ?? 'neutral'"
            >
                <template #icon><Bed class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.overview_at_risk_students')"
                :value="formatCount(summary.atRiskStudents)"
                :subtext="atRiskSubtext"
                :trend="summary.atRiskTrend ?? 'neutral'"
            >
                <template #icon><AlertTriangle class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.overview_priority_alerts')">
                <Empty v-if="priorityAlerts.length === 0" :message="$t('dashboard.overview_no_alerts')" />
                <div v-else class="flex flex-col gap-0">
                    <div
                        v-for="(alert, index) in priorityAlerts"
                        :key="index"
                        class="flex gap-3 border-b border-gray-100 py-2 last:border-0"
                    >
                        <div
                            class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                            :class="alertDotClass(alert.severity)"
                        ></div>
                        <div>
                            <div class="text-sm leading-snug text-gray-900">{{ alert.message }}</div>
                            <div class="mt-0.5 text-xs text-gray-500">{{ formatAlertTime(alert.updatedAt) }}</div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.overview_enrolment_by_department')">
                <Empty
                    v-if="enrolmentByDepartment.length === 0"
                    :message="$t('dashboard.overview_no_enrolment_data')"
                />
                <div v-else class="mt-1 flex flex-col gap-2">
                    <div
                        v-for="(row, index) in enrolmentByDepartment"
                        :key="row.departmentId"
                        class="flex items-center gap-2"
                    >
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">{{ row.departmentName }}</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div
                                class="h-2 rounded-sm"
                                :class="departmentBarColors[index % departmentBarColors.length]"
                                :style="{ width: `${row.barPercent}%` }"
                            ></div>
                        </div>
                        <div class="w-12 text-right text-xs text-gray-500">{{ row.count.toLocaleString() }}</div>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
