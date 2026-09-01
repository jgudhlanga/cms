<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { useDashboardStore } from '@/store/dashboard/useDashboardStore';
import {
    emptyStudentDashboardBreakdown,
    formatMetricCount,
    type OverviewDashboard,
    type StudentDashboardBreakdown,
} from '@/types/dashboard';
import { trans } from 'laravel-vue-i18n';
import {
    Bed,
    BookOpen,
    Briefcase,
    GraduationCap,
    Handshake,
    HeartPulse,
    User,
    UserRound,
    Users,
} from 'lucide-vue-next';
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

const { summary, enrolmentByDepartment, priorityAlerts, enrolmentFunnel, academicSnapshot, quickInsights } =
    props.overviewDashboard;

const studentBreakdown = computed(
    () => props.overviewDashboard.studentBreakdown ?? emptyStudentDashboardBreakdown(),
);

const notAvailable = computed(() => trans('dashboard.overview_not_available'));

const formatRate = (value: number | null): string => (value === null ? notAvailable.value : `${value}%`);

const metricSubtext = (value: string | null, fallback?: string): string =>
    value ?? fallback ?? notAvailable.value;

const hostelValue = computed(() => formatRate(summary.hostelOccupancyRate));

const hostelSubtext = computed(() => {
    if (summary.hostelSubtext) {
        return summary.hostelSubtext;
    }

    return notAvailable.value;
});

type BreakdownGroup = {
    key: string;
    label: string;
    cards: Array<{
        key: string;
        title: string;
        value: string | number;
        subtext: string;
        accent: string;
        icon: typeof User;
    }>;
};

const modeAccents = [
    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
    'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
];

const levelAccents = [
    'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
    'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
    'bg-lime-100 text-lime-700 dark:bg-lime-950 dark:text-lime-300',
    'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
];

const visibleLevels = (breakdown: StudentDashboardBreakdown) =>
    breakdown.byLevel.filter((row) => row.count > 0);

const visibleModes = (breakdown: StudentDashboardBreakdown) =>
    breakdown.byModeOfStudy.filter((row) => row.count > 0);

const studentBreakdownGroups = computed((): BreakdownGroup[] => {
    const breakdown = studentBreakdown.value;
    const groups: BreakdownGroup[] = [
        {
            key: 'gender',
            label: trans('dashboard.student_filter_gender'),
            cards: [
                {
                    key: 'male',
                    title: trans('students.stat_male'),
                    value: formatMetricCount(breakdown.male),
                    subtext: trans('dashboard.student_enrolled'),
                    accent: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
                    icon: User,
                },
                {
                    key: 'female',
                    title: trans('students.stat_female'),
                    value: formatMetricCount(breakdown.female),
                    subtext: trans('dashboard.student_enrolled'),
                    accent: 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300',
                    icon: UserRound,
                },
            ],
        },
    ];

    const modes = visibleModes(breakdown);
    if (modes.length > 0) {
        groups.push({
            key: 'mode',
            label: trans('dashboard.student_filter_mode'),
            cards: modes.map((mode, index) => ({
                key: `mode-${mode.id}`,
                title: mode.name,
                value: formatMetricCount(mode.count),
                subtext: trans('dashboard.student_enrolled'),
                accent: modeAccents[index % modeAccents.length],
                icon: BookOpen,
            })),
        });
    }

    if (breakdown.byStudentType.length > 0) {
        groups.push({
            key: 'type',
            label: trans('dashboard.student_filter_type'),
            cards: breakdown.byStudentType.map((type) => ({
                key: `type-${type.id}`,
                title: type.name,
                value: formatMetricCount(type.count),
                subtext: trans('dashboard.student_enrolled'),
                accent:
                    type.id === 'apprentice'
                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                        : 'bg-slate-100 text-slate-700 dark:bg-slate-950 dark:text-slate-300',
                icon: type.id === 'apprentice' ? Briefcase : User,
            })),
        });
    }

    if (breakdown.bySponsored.length > 0) {
        groups.push({
            key: 'sponsor',
            label: trans('dashboard.student_filter_sponsor'),
            cards: breakdown.bySponsored.map((row) => ({
                key: `sponsored-${row.id}`,
                title: row.name,
                value: formatMetricCount(row.count),
                subtext: trans('dashboard.student_enrolled'),
                accent:
                    row.id === 'sponsored'
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'bg-gray-100 text-gray-700 dark:bg-gray-950 dark:text-gray-300',
                icon: row.id === 'sponsored' ? Handshake : User,
            })),
        });
    }

    if (breakdown.byDisability.length > 0) {
        groups.push({
            key: 'disability',
            label: trans('dashboard.student_filter_disability'),
            cards: breakdown.byDisability.map((row) => ({
                key: `disability-${row.id}`,
                title: row.name,
                value: formatMetricCount(row.count),
                subtext: trans('dashboard.student_enrolled'),
                accent:
                    row.id === 'yes'
                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300'
                        : 'bg-gray-100 text-gray-700 dark:bg-gray-950 dark:text-gray-300',
                icon: HeartPulse,
            })),
        });
    }

    const levels = visibleLevels(breakdown);
    if (levels.length > 0) {
        groups.push({
            key: 'level',
            label: trans('dashboard.student_filter_level'),
            cards: levels.map((level, index) => ({
                key: `level-${level.id}`,
                title: level.name,
                value: formatMetricCount(level.count),
                subtext: trans('dashboard.student_enrolled'),
                accent: levelAccents[index % levelAccents.length],
                icon: GraduationCap,
            })),
        });
    }

    return groups;
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
    if (rate >= 25) return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300';
    if (rate >= 15) return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
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
    <div class="mt-4 flex flex-col gap-3">
        <div v-if="quickInsights.length > 0" class="flex flex-wrap gap-2">
            <span class="text-xs font-medium text-muted-foreground">{{ $t('dashboard.overview_quick_insights') }}:</span>
            <span
                v-for="insight in quickInsights"
                :key="insight.key"
                class="rounded-full bg-muted px-2.5 py-0.5 text-xs text-foreground"
            >
                {{ insight.message }}
            </span>
        </div>

        <div class="flex flex-wrap items-start gap-x-4 gap-y-2.5">
            <div v-if="showSection('hostel') || showSection('staff')" class="flex flex-col gap-1.5">
                <div class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                    {{ $t('dashboard.overview_key_metrics') }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <MetricCard
                        v-if="showSection('hostel')"
                        compact
                        class="w-32"
                        accent="bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300"
                        :title="$t('dashboard.overview_hostel_occupancy')"
                        :value="hostelValue"
                        :subtext="hostelSubtext"
                        trend="neutral"
                    >
                        <template #icon><Bed class="h-3.5 w-3.5" /></template>
                    </MetricCard>
                    <MetricCard
                        v-if="showSection('staff')"
                        compact
                        class="w-32"
                        accent="bg-slate-100 text-slate-700 dark:bg-slate-950 dark:text-slate-300"
                        :title="$t('dashboard.overview_total_staff')"
                        :value="formatMetricCount(summary.totalStaff)"
                        :subtext="metricSubtext(summary.totalStaffSubtext)"
                        trend="neutral"
                    >
                        <template #icon><Users class="h-3.5 w-3.5" /></template>
                    </MetricCard>
                </div>
            </div>

            <div v-for="group in studentBreakdownGroups" :key="group.key" class="flex flex-col gap-1.5">
                <div class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                    {{ group.label }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <MetricCard
                        v-for="card in group.cards"
                        :key="card.key"
                        compact
                        class="w-32"
                        :accent="card.accent"
                        :title="card.title"
                        :value="card.value"
                        :subtext="card.subtext"
                        trend="neutral"
                    >
                        <template #icon><component :is="card.icon" class="h-3.5 w-3.5" /></template>
                    </MetricCard>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <DashboardCard v-if="showPriorityAlerts" :title="$t('dashboard.overview_priority_alerts')">
                <Empty v-if="priorityAlerts.length === 0" :message="$t('dashboard.overview_no_alerts')" />
                <div v-else class="flex flex-col gap-0">
                    <div
                        v-for="(alert, index) in priorityAlerts"
                        :key="index"
                        class="flex gap-3 border-b border-border/60 py-2 last:border-0"
                    >
                        <div
                            class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                            :class="alertDotClass(alert.severity)"
                        ></div>
                        <div>
                            <div class="text-sm leading-snug text-foreground">{{ alert.message }}</div>
                            <div
                                v-if="alert.updatedAt && alert.updatedAt !== 'N/A'"
                                class="mt-0.5 text-xs text-muted-foreground"
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
                    class="mb-3 rounded text-xs text-emerald-700 hover:underline focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none"
                    @click="switchTab('enrolments')"
                >
                    {{ $t('dashboard.overview_view_enrolments') }} →
                </button>
                <div class="flex flex-col gap-3">
                    <div v-for="step in funnelSteps" :key="step.key" class="flex flex-col gap-0.5">
                        <div class="flex items-center gap-3">
                            <div class="w-28 shrink-0 text-xs text-foreground">{{ step.label }}</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div
                                    class="h-2 rounded-sm bg-emerald-500"
                                    :style="{ width: `${Math.round((step.count / funnelMax) * 100)}%` }"
                                ></div>
                            </div>
                            <div class="w-12 text-right text-xs font-medium tabular-nums text-foreground">
                                {{ step.count.toLocaleString() }}
                            </div>
                        </div>
                        <div v-if="step.rateLabel" class="pl-28 text-[10px] text-muted-foreground">{{ step.rateLabel }}</div>
                    </div>
                    <div
                        v-if="enrolmentFunnel.provisional > 0"
                        class="mt-1 flex items-center gap-2 rounded-md bg-amber-50 px-2 py-1.5 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                    >
                        <span>{{ $t('dashboard.overview_funnel_provisional') }}:</span>
                        <span class="font-medium">{{ enrolmentFunnel.provisional.toLocaleString() }}</span>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard v-if="showSection('academic')" :title="$t('dashboard.overview_academic_snapshot')">
                <button
                    type="button"
                    class="mb-3 rounded text-xs text-emerald-700 hover:underline focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none"
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
                        <div class="text-xs font-medium text-muted-foreground">{{ $t('dashboard.academic_grade_distribution') }}</div>
                        <div
                            v-for="segment in academicSnapshot.gradeSegments"
                            :key="segment.key"
                            class="flex items-center gap-2"
                        >
                            <div class="w-20 shrink-0 truncate text-xs text-foreground">{{ segment.label }}</div>
                            <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                                <div
                                    class="h-2 rounded-sm"
                                    :class="gradeBarColors[segment.key] ?? 'bg-gray-400'"
                                    :style="{ width: `${segment.percent}%` }"
                                ></div>
                            </div>
                            <div class="w-16 text-right text-xs tabular-nums text-muted-foreground">
                                {{ segment.count.toLocaleString() }} ({{ segment.percent }}%)
                            </div>
                        </div>
                    </div>
                    <div v-if="academicSnapshot.topFailureHotspots.length > 0">
                        <div class="mb-2 text-xs font-medium text-muted-foreground">
                            {{ $t('dashboard.academic_module_failure_hotspots') }}
                        </div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="hotspot in academicSnapshot.topFailureHotspots"
                                :key="hotspot.moduleId"
                                class="flex items-center justify-between gap-2 text-xs"
                            >
                                <span class="truncate text-foreground">{{ hotspot.moduleName }}</span>
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
                        <div class="w-32 shrink-0 truncate text-xs text-foreground">{{ row.departmentName }}</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-2 rounded-sm"
                                :class="departmentBarColors[index % departmentBarColors.length]"
                                :style="{ width: `${row.barPercent}%` }"
                            ></div>
                        </div>
                        <div class="w-12 text-right text-xs tabular-nums text-muted-foreground">{{ row.count.toLocaleString() }}</div>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
