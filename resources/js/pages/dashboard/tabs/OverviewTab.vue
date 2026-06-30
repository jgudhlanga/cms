<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { useDashboardStore } from '@/store/dashboard/useDashboardStore';
import type { OverviewDashboard } from '@/types/dashboard';
import { trans } from 'laravel-vue-i18n';
import { AlertTriangle, BarChart3, Bed, ClipboardList, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

interface Props {
    overviewDashboard: OverviewDashboard;
    visibleTabs: string[];
}

const props = defineProps<Props>();

const dashboardStore = useDashboardStore();

const showSection = (tab: string): boolean => props.visibleTabs.includes(tab);

const showPriorityAlerts = computed(
    () => showSection('academic') || showSection('hostel') || showSection('enrolments'),
);

const visibleMetricCount = computed(() => {
    let count = 0;

    if (showSection('academic')) {
        count += 3;
    }

    if (showSection('hostel')) {
        count += 1;
    }

    if (showSection('staff')) {
        count += 1;
    }

    return count;
});

const metricGridClass = computed(() => {
    const count = visibleMetricCount.value;

    if (count <= 1) {
        return 'grid-cols-1';
    }

    if (count === 2) {
        return 'grid-cols-1 sm:grid-cols-2';
    }

    if (count === 3) {
        return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
    }

    if (count === 4) {
        return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
    }

    return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5';
});

const { summary, enrolmentByDepartment, priorityAlerts, enrolmentFunnel, academicSnapshot, quickInsights } =
    props.overviewDashboard;

const notAvailable = computed(() => trans('dashboard.overview_not_available'));

const formatRate = (value: number | null): string => (value === null ? notAvailable.value : `${value}%`);

const formatCount = (value: number | null): string =>
    value === null ? notAvailable.value : value.toLocaleString();

const metricSubtext = (value: string | null, fallback?: string): string =>
    value ?? fallback ?? notAvailable.value;

const hostelValue = computed(() => formatRate(summary.hostelOccupancyRate));

const hostelSubtext = computed(() => {
    if (summary.hostelSubtext) {
        return summary.hostelSubtext;
    }

    return notAvailable.value;
});

const funnelSteps = computed(() => [
    {
        key: 'applications',
        label: trans('dashboard.overview_funnel_applications'),
        count: enrolmentFunnel.applications,
        rate: enrolmentFunnel.acceptanceRate,
        rateLabel: trans('dashboard.acceptance_rate', {
            rate: String(enrolmentFunnel.acceptanceRate ?? 0),
        }),
    },
    {
        key: 'offersMade',
        label: trans('dashboard.overview_funnel_offers'),
        count: enrolmentFunnel.offersMade,
        rate: null,
        rateLabel: null,
    },
    {
        key: 'confirmed',
        label: trans('dashboard.overview_funnel_confirmed'),
        count: enrolmentFunnel.confirmed,
        rate: enrolmentFunnel.yieldRate,
        rateLabel: trans('dashboard.yield_rate', {
            rate: String(enrolmentFunnel.yieldRate ?? 0),
        }),
    },
    {
        key: 'waitlisted',
        label: trans('dashboard.overview_funnel_waitlisted'),
        count: enrolmentFunnel.waitlisted,
        rate: null,
        rateLabel: null,
    },
]);

const funnelMax = computed(() => Math.max(...funnelSteps.value.map((step) => step.count), 1));

const gradeBarColors: Record<string, string> = {
    distinction: 'bg-blue-500',
    merit: 'bg-indigo-500',
    pass: 'bg-emerald-500',
    fail: 'bg-rose-500',
};

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

const failureBadgeClass = (rate: number): string => {
    if (rate >= 25) return 'bg-rose-100 text-rose-700';
    if (rate >= 15) return 'bg-amber-100 text-amber-700';

    return 'bg-emerald-100 text-emerald-700';
};

const formatAlertTime = (updatedAt: string | null): string => {
    if (!updatedAt) {
        return notAvailable.value;
    }

    return new Date(updatedAt).toLocaleString();
};

const switchTab = (tab: string) => {
    dashboardStore.activeTab = tab;
};
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div v-if="quickInsights.length > 0" class="flex flex-wrap gap-2">
            <span class="text-xs font-medium text-gray-500">{{ $t('dashboard.overview_quick_insights') }}:</span>
            <span
                v-for="insight in quickInsights"
                :key="insight.key"
                class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-700"
            >
                {{ insight.message }}
            </span>
        </div>

        <div class="grid gap-4" :class="metricGridClass">
            <MetricCard
                v-if="showSection('academic')"
                :title="$t('dashboard.overview_pass_rate')"
                :value="formatRate(summary.passRate)"
                :subtext="metricSubtext(summary.passRateSubtext)"
                trend="neutral"
            >
                <template #icon><BarChart3 class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                v-if="showSection('academic')"
                :title="$t('dashboard.overview_mark_completion')"
                :value="formatRate(summary.markCompletionRate)"
                :subtext="metricSubtext(summary.markCompletionSubtext)"
                trend="neutral"
            >
                <template #icon><ClipboardList class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                v-if="showSection('academic')"
                :title="$t('dashboard.overview_at_risk_students')"
                :value="formatCount(summary.atRiskStudents)"
                :subtext="metricSubtext(summary.atRiskSubtext)"
                trend="warning"
            >
                <template #icon><AlertTriangle class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                v-if="showSection('hostel')"
                :title="$t('dashboard.overview_hostel_occupancy')"
                :value="hostelValue"
                :subtext="hostelSubtext"
                trend="neutral"
            >
                <template #icon><Bed class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                v-if="showSection('staff')"
                :title="$t('dashboard.overview_total_staff')"
                :value="summary.totalStaff.toLocaleString()"
                :subtext="metricSubtext(summary.totalStaffSubtext)"
                trend="neutral"
            >
                <template #icon><Users class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard v-if="showPriorityAlerts" :title="$t('dashboard.overview_priority_alerts')">
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
                            <div
                                v-if="alert.updatedAt && alert.updatedAt !== 'N/A'"
                                class="mt-0.5 text-xs text-gray-500"
                            >
                                {{ formatAlertTime(alert.updatedAt) }}
                            </div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard v-if="showSection('enrolments')" :title="$t('dashboard.overview_enrolment_funnel')">
                <button
                    type="button"
                    class="mb-3 text-xs text-emerald-700 hover:underline"
                    @click="switchTab('enrolments')"
                >
                    {{ $t('dashboard.overview_view_enrolments') }} →
                </button>
                <div class="flex flex-col gap-3">
                    <div v-for="step in funnelSteps" :key="step.key" class="flex flex-col gap-0.5">
                        <div class="flex items-center gap-3">
                            <div class="w-28 shrink-0 text-xs text-gray-700">{{ step.label }}</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div
                                    class="h-2 rounded-sm bg-emerald-500"
                                    :style="{ width: `${Math.round((step.count / funnelMax) * 100)}%` }"
                                ></div>
                            </div>
                            <div class="w-12 text-right text-xs font-medium text-gray-900">
                                {{ step.count.toLocaleString() }}
                            </div>
                        </div>
                        <div v-if="step.rateLabel" class="pl-28 text-[10px] text-gray-500">{{ step.rateLabel }}</div>
                    </div>
                    <div
                        v-if="enrolmentFunnel.provisional > 0"
                        class="mt-1 flex items-center gap-2 rounded-md bg-amber-50 px-2 py-1.5 text-xs text-amber-800"
                    >
                        <span>{{ $t('dashboard.overview_funnel_provisional') }}:</span>
                        <span class="font-medium">{{ enrolmentFunnel.provisional.toLocaleString() }}</span>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard v-if="showSection('academic')" :title="$t('dashboard.overview_academic_snapshot')">
                <button
                    type="button"
                    class="mb-3 text-xs text-emerald-700 hover:underline"
                    @click="switchTab('academic')"
                >
                    {{ $t('dashboard.overview_view_academic') }} →
                </button>
                <Empty
                    v-if="academicSnapshot.gradeSegments.length === 0 && academicSnapshot.topFailureHotspots.length === 0"
                    :message="$t('dashboard.academic_no_grade_data')"
                />
                <div v-else class="flex flex-col gap-4">
                    <div v-if="academicSnapshot.gradeSegments.length > 0" class="flex flex-col gap-2">
                        <div class="text-xs font-medium text-gray-500">{{ $t('dashboard.academic_grade_distribution') }}</div>
                        <div
                            v-for="segment in academicSnapshot.gradeSegments"
                            :key="segment.key"
                            class="flex items-center gap-2"
                        >
                            <div class="w-20 shrink-0 truncate text-xs text-gray-900">{{ segment.label }}</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                                <div
                                    class="h-2 rounded-sm"
                                    :class="gradeBarColors[segment.key] ?? 'bg-gray-400'"
                                    :style="{ width: `${segment.percent}%` }"
                                ></div>
                            </div>
                            <div class="w-16 text-right text-xs text-gray-500">
                                {{ segment.count.toLocaleString() }} ({{ segment.percent }}%)
                            </div>
                        </div>
                    </div>
                    <div v-if="academicSnapshot.topFailureHotspots.length > 0">
                        <div class="mb-2 text-xs font-medium text-gray-500">
                            {{ $t('dashboard.academic_module_failure_hotspots') }}
                        </div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="hotspot in academicSnapshot.topFailureHotspots"
                                :key="hotspot.moduleId"
                                class="flex items-center justify-between gap-2 text-xs"
                            >
                                <span class="truncate text-gray-900">{{ hotspot.moduleName }}</span>
                                <span
                                    class="shrink-0 rounded px-1.5 py-0.5 font-medium"
                                    :class="failureBadgeClass(hotspot.rate)"
                                >
                                    {{ hotspot.rate }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard v-if="showSection('enrolments')" :title="$t('dashboard.overview_enrolment_by_department')">
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
