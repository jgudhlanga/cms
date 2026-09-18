<script setup lang="ts">
import CardEmpty from '../components/CardEmpty.vue';
import { useDashboardStore } from '@/store/dashboard/useDashboardStore';
import {
    emptyStudentDashboardBreakdown,
    formatMetricCount,
    type OverviewDashboard,
} from '@/types/dashboard';
import { trans } from 'laravel-vue-i18n';
import { Bed, CalendarRange, ClipboardList, FileText, Sparkles, UserPlus, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import DataRow from '../components/DataRow.vue';
import MetricCard from '../components/MetricCard.vue';
import { cycleTones, type Tone } from '../components/tones';

interface Props {
    overviewDashboard: OverviewDashboard;
    visibleTabs: string[];
    /** Department and funnel figures are scoped to this intake period. */
    intakePeriodName?: string | null;
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

const percentOf = (count: number, total: number): number => (total <= 0 ? 0 : Math.round((count / total) * 100));

type KpiTile = {
    key: string;
    title: string;
    value: string;
    subtext: string;
    tone: Tone;
    badge: string | null;
    icon: typeof Users;
};

const kpiTiles = computed((): KpiTile[] => {
    const breakdown = studentBreakdown.value;
    const tiles: KpiTile[] = [
        {
            key: 'students',
            title: trans('dashboard.overview_total_students'),
            value: formatMetricCount(breakdown.total),
            subtext: trans('dashboard.overview_gender_split_subtext', {
                male: formatMetricCount(breakdown.male),
                female: formatMetricCount(breakdown.female),
            }),
            tone: 'indigo',
            badge: null,
            icon: Users,
        },
    ];

    if (showSection('enrolments')) {
        tiles.push(
            {
                key: 'applications',
                title: trans('dashboard.overview_applications'),
                value: formatMetricCount(enrolmentFunnel.applications),
                subtext: trans('dashboard.acceptance_rate', {
                    rate: String(enrolmentFunnel.acceptanceRate ?? 0),
                }),
                tone: 'sky',
                badge: enrolmentFunnel.offersMade > 0 ? formatMetricCount(enrolmentFunnel.offersMade) : null,
                icon: FileText,
            },
            {
                key: 'confirmed',
                title: trans('dashboard.overview_confirmed_students'),
                value: formatMetricCount(enrolmentFunnel.confirmed),
                subtext: trans('dashboard.yield_rate', {
                    rate: String(enrolmentFunnel.yieldRate ?? 0),
                }),
                tone: 'emerald',
                badge: null,
                icon: UserPlus,
            },
        );
    }

    if (showSection('academic')) {
        tiles.push({
            key: 'markCompletion',
            title: trans('dashboard.overview_mark_completion'),
            value: formatRate(academicSnapshot.markCompletion.completeRate),
            subtext: `${formatMetricCount(academicSnapshot.markCompletion.completeCount)} / ${formatMetricCount(academicSnapshot.markCompletion.expectedModuleResults)}`,
            tone: 'amber',
            badge: null,
            icon: ClipboardList,
        });
    }

    if (showSection('staff')) {
        tiles.push({
            key: 'staff',
            title: trans('dashboard.overview_total_staff'),
            value: formatMetricCount(summary.totalStaff),
            subtext: summary.totalStaffSubtext ?? notAvailable.value,
            tone: 'violet',
            badge: null,
            icon: Users,
        });
    }

    if (showSection('hostel')) {
        tiles.push({
            key: 'hostel',
            title: trans('dashboard.overview_hostel_occupancy'),
            value: formatRate(summary.hostelOccupancyRate),
            subtext: summary.hostelSubtext ?? notAvailable.value,
            tone: 'teal',
            badge: null,
            icon: Bed,
        });
    }

    return tiles;
});

const funnelSteps = computed(() => {
    const max = Math.max(
        enrolmentFunnel.applications,
        enrolmentFunnel.offersMade,
        enrolmentFunnel.confirmed,
        enrolmentFunnel.waitlisted,
        1,
    );

    return [
        { key: 'applications', label: trans('dashboard.overview_funnel_applications'), count: enrolmentFunnel.applications, tone: 'sky' as Tone },
        { key: 'offersMade', label: trans('dashboard.overview_funnel_offers'), count: enrolmentFunnel.offersMade, tone: 'blue' as Tone },
        { key: 'confirmed', label: trans('dashboard.overview_funnel_confirmed'), count: enrolmentFunnel.confirmed, tone: 'emerald' as Tone },
        { key: 'waitlisted', label: trans('dashboard.overview_funnel_waitlisted'), count: enrolmentFunnel.waitlisted, tone: 'amber' as Tone },
    ].map((step) => ({ ...step, percent: percentOf(step.count, max) }));
});

type DemographicRow = { key: string; label: string; count: number; percent: number; tone: Tone };

const demographicRows = computed((): DemographicRow[] => {
    const breakdown = studentBreakdown.value;
    const total = breakdown.total || 1;

    const rows: DemographicRow[] = [
        {
            key: 'male',
            label: trans('students.stat_male'),
            count: breakdown.male,
            percent: percentOf(breakdown.male, total),
            tone: 'blue',
        },
        {
            key: 'female',
            label: trans('students.stat_female'),
            count: breakdown.female,
            percent: percentOf(breakdown.female, total),
            tone: 'pink',
        },
    ];

    breakdown.bySponsored.forEach((row) => {
        rows.push({
            key: `sponsored-${row.id}`,
            label: row.name,
            count: row.count,
            percent: percentOf(row.count, total),
            tone: row.id === 'sponsored' ? 'emerald' : 'slate',
        });
    });

    breakdown.byDisability.forEach((row) => {
        rows.push({
            key: `disability-${row.id}`,
            label: row.name,
            count: row.count,
            percent: percentOf(row.count, total),
            tone: row.id === 'yes' ? 'violet' : 'slate',
        });
    });

    return rows;
});

const levelTones: Tone[] = ['purple', 'cyan', 'lime', 'rose', 'fuchsia', 'indigo'];
const modeTones: Tone[] = ['indigo', 'violet', 'teal', 'orange'];
const departmentTones: Tone[] = ['blue', 'emerald', 'indigo', 'pink', 'orange', 'teal', 'violet', 'cyan', 'slate'];

const levelRows = computed(() => {
    const rows = studentBreakdown.value.byLevel.filter((row) => row.count > 0);
    const max = Math.max(...rows.map((row) => row.count), 1);

    return rows.map((row, index) => ({
        ...row,
        percent: percentOf(row.count, max),
        tone: cycleTones(levelTones, index),
    }));
});

const modeRows = computed(() => {
    const rows = studentBreakdown.value.byModeOfStudy.filter((row) => row.count > 0);
    const max = Math.max(...rows.map((row) => row.count), 1);

    return rows.map((row, index) => ({
        ...row,
        percent: percentOf(row.count, max),
        tone: cycleTones(modeTones, index),
    }));
});

const typeRows = computed(() => {
    const rows = studentBreakdown.value.byStudentType;
    const max = Math.max(...rows.map((row) => row.count), 1);

    return rows.map((row) => ({
        ...row,
        percent: percentOf(row.count, max),
        tone: (row.id === 'apprentice' ? 'amber' : 'indigo') as Tone,
    }));
});

const departmentRows = computed(() =>
    enrolmentByDepartment.map((row, index) => ({
        ...row,
        tone: cycleTones(departmentTones, index),
    })),
);

const gradeTones: Record<string, Tone> = {
    distinction: 'blue',
    merit: 'indigo',
    pass: 'emerald',
    fail: 'rose',
};

const alertStyles: Record<string, { rail: string; dot: string }> = {
    critical: { rail: 'bg-rose-500', dot: 'bg-rose-500' },
    warning: { rail: 'bg-amber-500', dot: 'bg-amber-500' },
    success: { rail: 'bg-emerald-500', dot: 'bg-emerald-500' },
    info: { rail: 'bg-sky-500', dot: 'bg-sky-500' },
};

const alertStyle = (severity: string) => alertStyles[severity] ?? alertStyles.info;

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

const linkClass =
    'rounded text-[10px] font-semibold tracking-[0.06em] text-primary uppercase hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none';
</script>

<template>
    <div class="mt-3 flex flex-col gap-2.5">
        <div
            v-if="quickInsights.length > 0"
            class="flex items-center gap-2 overflow-x-auto rounded-xl border border-border/60 bg-linear-to-r from-muted/60 via-muted/30 to-transparent px-3 py-2"
        >
            <Sparkles class="h-3.5 w-3.5 shrink-0 text-primary" />
            <span class="shrink-0 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                {{ $t('dashboard.overview_quick_insights') }}
            </span>
            <div class="flex items-center gap-1.5">
                <span
                    v-for="insight in quickInsights"
                    :key="insight.key"
                    class="shrink-0 rounded-full border border-border/60 bg-card px-2 py-0.5 text-[11px] text-foreground shadow-xs"
                >
                    {{ insight.message }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <MetricCard
                v-for="tile in kpiTiles"
                :key="tile.key"
                :tone="tile.tone"
                :title="tile.title"
                :value="tile.value"
                :subtext="tile.subtext"
                :badge="tile.badge"
                trend="neutral"
            >
                <template #icon><component :is="tile.icon" class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2 xl:grid-cols-3">
            <DashboardCard v-if="showSection('enrolments')" :title="$t('dashboard.overview_enrolment_funnel')">
                <template #action>
                    <button type="button" :class="linkClass" @click="switchTab('enrolments')">
                        {{ $t('dashboard.overview_view_enrolments') }} →
                    </button>
                </template>
                <div class="flex flex-col gap-2">
                    <DataRow
                        v-for="step in funnelSteps"
                        :key="step.key"
                        :label="step.label"
                        :value="step.count.toLocaleString()"
                        :percent="step.percent"
                        :tone="step.tone"
                        label-width-class="w-20"
                    />
                </div>
                <div class="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-border/50 pt-2">
                    <span
                        class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-medium text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                    >
                        {{ $t('dashboard.acceptance_rate', { rate: String(enrolmentFunnel.acceptanceRate ?? 0) }) }}
                    </span>
                    <span
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        {{ $t('dashboard.yield_rate', { rate: String(enrolmentFunnel.yieldRate ?? 0) }) }}
                    </span>
                    <span
                        v-if="enrolmentFunnel.provisional > 0"
                        class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                    >
                        {{ $t('dashboard.overview_funnel_provisional') }}:
                        {{ enrolmentFunnel.provisional.toLocaleString() }}
                    </span>
                </div>
            </DashboardCard>

            <DashboardCard v-if="showPriorityAlerts" :title="$t('dashboard.overview_priority_alerts')">
                <CardEmpty v-if="priorityAlerts.length === 0" :message="$t('dashboard.overview_no_alerts')" />
                <div v-else class="flex flex-col gap-1">
                    <div
                        v-for="(alert, index) in priorityAlerts"
                        :key="index"
                        class="relative flex gap-2 overflow-hidden rounded-md bg-muted/30 py-1.5 pr-2 pl-2.5 transition-colors hover:bg-muted/60"
                    >
                        <div class="absolute inset-y-0 left-0 w-0.5" :class="alertStyle(alert.severity).rail" />
                        <div
                            class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="alertStyle(alert.severity).dot"
                        />
                        <div class="min-w-0">
                            <div class="text-[11px] leading-snug text-foreground">{{ alert.message }}</div>
                            <div
                                v-if="alert.updatedAt && alert.updatedAt !== 'N/A'"
                                class="mt-0.5 text-[10px] text-muted-foreground"
                            >
                                {{ formatAlertTime(alert.updatedAt) }}
                            </div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.overview_demographics')">
                <div class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in demographicRows"
                        :key="row.key"
                        :label="row.label"
                        :value="formatMetricCount(row.count)"
                        :percent="row.percent"
                        :tone="row.tone"
                        label-width-class="w-24"
                        value-width-class="w-14"
                    />
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.overview_levels_breakdown')">
                <CardEmpty v-if="levelRows.length === 0" :message="notAvailable" />
                <div v-else class="grid grid-cols-2 gap-x-3 gap-y-0.5">
                    <DataRow
                        v-for="row in levelRows"
                        :key="row.id"
                        variant="inline"
                        :label="row.name"
                        :value="formatMetricCount(row.count)"
                        :percent="row.percent"
                        :tone="row.tone"
                    />
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.overview_mode_of_study')">
                <CardEmpty v-if="modeRows.length === 0" :message="notAvailable" />
                <div v-else class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in modeRows"
                        :key="row.id"
                        :label="row.name"
                        :value="formatMetricCount(row.count)"
                        :percent="row.percent"
                        :tone="row.tone"
                        label-width-class="w-24"
                        value-width-class="w-14"
                    />
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.overview_enrolment_type')">
                <CardEmpty v-if="typeRows.length === 0" :message="notAvailable" />
                <div v-else class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in typeRows"
                        :key="row.id"
                        :label="row.name"
                        :value="formatMetricCount(row.count)"
                        :percent="row.percent"
                        :tone="row.tone"
                        label-width-class="w-24"
                        value-width-class="w-14"
                    />
                </div>
            </DashboardCard>

            <DashboardCard v-if="showSection('enrolments')" :title="$t('dashboard.overview_enrolment_by_department')">
                <template v-if="intakePeriodName" #action>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary"
                    >
                        <CalendarRange class="h-3 w-3" />
                        {{ intakePeriodName }}
                    </span>
                </template>
                <CardEmpty
                    v-if="departmentRows.length === 0"
                    :message="$t('dashboard.overview_no_enrolment_data')"
                />
                <div v-else class="flex flex-col gap-0.5">
                    <DataRow
                        v-for="row in departmentRows"
                        :key="row.departmentId"
                        variant="inline"
                        :label="row.departmentName"
                        :value="row.count.toLocaleString()"
                        :percent="row.barPercent"
                        :tone="row.tone"
                    />
                </div>
            </DashboardCard>

            <DashboardCard v-if="showSection('academic')" :title="$t('dashboard.overview_academic_snapshot')">
                <template #action>
                    <button type="button" :class="linkClass" @click="switchTab('academic')">
                        {{ $t('dashboard.overview_view_academic') }} →
                    </button>
                </template>
                <CardEmpty
                    v-if="academicSnapshot.gradeSegments.length === 0 && academicSnapshot.topFailureHotspots.length === 0"
                    :message="$t('dashboard.academic_no_grade_data')"
                />
                <div v-else class="flex flex-col gap-2.5">
                    <div v-if="academicSnapshot.gradeSegments.length > 0" class="flex flex-col gap-2">
                        <DataRow
                            v-for="segment in academicSnapshot.gradeSegments"
                            :key="segment.key"
                            :label="segment.label"
                            :value="`${segment.percent}%`"
                            :percent="segment.percent"
                            :tone="gradeTones[segment.key] ?? 'slate'"
                            label-width-class="w-20"
                        />
                    </div>
                    <div v-if="academicSnapshot.topFailureHotspots.length > 0" class="border-t border-border/50 pt-2">
                        <div class="mb-1.5 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                            {{ $t('dashboard.academic_module_failure_hotspots') }}
                        </div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="hotspot in academicSnapshot.topFailureHotspots"
                                :key="hotspot.moduleId"
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="truncate text-[11px] text-foreground">{{ hotspot.moduleName }}</span>
                                <span
                                    class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-medium tabular-nums"
                                    :class="failureBadgeClass(hotspot.rate)"
                                >
                                    {{ hotspot.rate }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
