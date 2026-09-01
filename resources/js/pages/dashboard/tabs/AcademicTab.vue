<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import AssessmentCalendarWindowsList from '@/components/assessments/AssessmentCalendarWindowsList.vue';
import type { AcademicDashboard } from '@/types/dashboard';
import { emptyStudentDashboardBreakdown, formatMetricCount } from '@/types/dashboard';
import type { AssessmentCalendarWindow } from '@/types/assessments';
import { Chart, registerables } from 'chart.js';
import { trans } from 'laravel-vue-i18n';
import { AlertTriangle, Briefcase, ClipboardList, GraduationCap, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

Chart.register(...registerables);

interface Props {
    academicDashboard: AcademicDashboard;
}

const props = defineProps<Props>();

const {
    summary,
    courseWorkStatus,
    gradeDistribution,
    passRateByDepartment,
    passRateByLevel,
    passRateByCourse,
    topPerformingCourses,
    bottomPerformingCourses,
    moduleFailureHotspots,
    missingMarksByDepartment,
    missingMarksByLevel,
    missingMarksByCourse,
    missingMarksByModule,
    lecturerMarkingStats,
    attachmentTotal,
    attachmentCalendarYear,
} = props.academicDashboard;

const assessmentCalendars = computed<AssessmentCalendarWindow[]>(
    () => props.academicDashboard.assessmentCalendars ?? [],
);

const missingMarksReportUrl = computed(() => props.academicDashboard.missingMarksReportUrl ?? null);

const notAvailable = computed(() => trans('dashboard.academic_not_available'));

const formatRate = (value: number | null): string => (value === null ? notAvailable.value : `${value}%`);

const formatCount = (value: number | null): string => (value === null ? notAvailable.value : String(value));

const levelAccents = [
    'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
    'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
    'bg-lime-100 text-lime-700 dark:bg-lime-950 dark:text-lime-300',
    'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
];

const visibleLevels = computed(
    () => (props.academicDashboard.studentBreakdown ?? emptyStudentDashboardBreakdown()).byLevel.filter((row) => (row.count ?? 0) > 0),
);

const passRateBarClass = (rate: number): string => {
    if (rate >= 80) return 'bg-emerald-500';
    if (rate >= 70) return 'bg-orange-400';

    return 'bg-rose-500';
};

const failureBadgeClass = (rate: number): string => {
    if (rate >= 25) return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300';
    if (rate >= 15) return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
};

const incompleteBadgeClass = (rate: number): string => {
    if (rate >= 50) return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300';
    if (rate >= 25) return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
};

const attachmentSubtext = computed(() =>
    attachmentTotal !== null
        ? trans('dashboard.academic_attachment_calendar_year', { year: attachmentCalendarYear })
        : notAvailable.value,
);

const gradeChart = ref<HTMLCanvasElement | null>(null);
let gradeChartInstance: Chart | null = null;

const getThemeColor = (token: string, fallback: string): string => {
    if (typeof window === 'undefined') {
        return fallback;
    }
    const value = getComputedStyle(document.documentElement).getPropertyValue(token).trim();
    return value ? `hsl(${value})` : fallback;
};

const segmentColors: Record<string, string> = {
    distinction: 'rgba(59, 130, 246, 0.8)',
    merit: 'rgba(99, 102, 241, 0.8)',
    pass: 'rgba(16, 185, 129, 0.8)',
    fail: 'rgba(244, 63, 94, 0.8)',
};

const gradeChartData = computed(() => {
    const segments = gradeDistribution.segments;

    return {
        labels: segments.map((segment) => segment.label),
        datasets: [
            {
                data: segments.map((segment) => segment.count),
                backgroundColor: segments.map((segment) => segmentColors[segment.key] ?? 'rgba(156, 163, 175, 0.8)'),
                borderWidth: 0,
            },
        ],
    };
});

const initGradeChart = () => {
    if (!gradeChart.value || gradeDistribution.segments.length === 0) {
        if (gradeChartInstance) {
            gradeChartInstance.destroy();
            gradeChartInstance = null;
        }

        return;
    }

    if (gradeChartInstance) {
        gradeChartInstance.destroy();
    }

    gradeChartInstance = new Chart(gradeChart.value, {
        type: 'bar',
        data: { ...gradeChartData.value },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: getThemeColor('--muted-foreground', '#64748B') },
                    grid: { color: getThemeColor('--border', '#F1F5F9'), drawBorder: false },
                },
                x: {
                    ticks: { color: getThemeColor('--muted-foreground', '#64748B') },
                    grid: { display: false },
                },
            },
        },
    });
};

onMounted(() => {
    initGradeChart();
});

watch(
    () => gradeDistribution.segments,
    () => {
        initGradeChart();
    },
    { deep: true },
);
</script>

<template>
    <div class="mt-4 flex flex-col gap-3">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
            <MetricCard
                compact
                accent="bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                :title="$t('dashboard.academic_mark_completion')"
                :value="formatRate(summary.markCompletionRate)"
                :subtext="`${courseWorkStatus.completeCount} / ${courseWorkStatus.expectedModuleResults}`"
                trend="neutral"
            >
                <template #icon><ClipboardList class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300"
                :title="$t('dashboard.academic_incomplete_marks')"
                :value="formatCount(courseWorkStatus.incompleteCount)"
                :subtext="formatRate(courseWorkStatus.incompleteRate)"
                trend="warning"
            >
                <template #icon><X class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                :title="$t('dashboard.academic_outstanding_marks')"
                :value="formatCount(courseWorkStatus.outstandingCount)"
                :subtext="notAvailable"
                trend="warning"
            >
                <template #icon><ClipboardList class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                :title="$t('dashboard.academic_at_risk_students')"
                :value="formatCount(summary.atRiskStudentCount)"
                :subtext="$t('dashboard.academic_at_risk_subtext')"
                trend="warning"
            >
                <template #icon><AlertTriangle class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300"
                :title="$t('dashboard.academic_attachment_total')"
                :value="formatCount(attachmentTotal)"
                :subtext="attachmentSubtext"
                trend="neutral"
            >
                <template #icon><Briefcase class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                v-for="(level, index) in visibleLevels"
                :key="`level-${level.id}`"
                compact
                :accent="levelAccents[index % levelAccents.length]"
                :title="level.name"
                :value="formatMetricCount(level.count)"
                :subtext="$t('dashboard.student_enrolled')"
                trend="neutral"
            >
                <template #icon><GraduationCap class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <DashboardCard compact :title="$t('assessments.dashboard_assessment_calendars')">
            <div v-if="assessmentCalendars.length === 0">
                <Empty :message="$t('assessments.dashboard_no_assessment_calendars')" />
            </div>
            <div v-else class="space-y-3">
                <AssessmentCalendarWindowsList :windows="assessmentCalendars" compact />
                <a
                    v-if="missingMarksReportUrl"
                    :href="missingMarksReportUrl"
                    class="inline-flex rounded text-xs font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    {{ $t('assessments.dashboard_view_missing_marks_report') }}
                </a>
            </div>
        </DashboardCard>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <DashboardCard compact :title="$t('dashboard.academic_grade_distribution')">
                <div v-if="gradeDistribution.segments.length === 0" class="h-[140px]">
                    <Empty :message="$t('dashboard.academic_no_grade_data')" />
                </div>
                <div v-else class="h-[140px] w-full">
                    <canvas ref="gradeChart"></canvas>
                </div>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_pass_rate_by_department')">
                <div v-if="passRateByDepartment.length === 0" class="h-[140px]">
                    <Empty :message="$t('dashboard.academic_no_department_pass_rates')" />
                </div>
                <div v-else class="flex flex-col gap-1.5">
                    <div
                        v-for="row in passRateByDepartment"
                        :key="row.departmentId"
                        class="flex items-center gap-2"
                    >
                        <div class="w-28 shrink-0 truncate text-xs text-foreground">{{ row.departmentName }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-1.5 rounded-sm"
                                :class="passRateBarClass(row.passRate)"
                                :style="{ width: `${row.barPercent}%` }"
                            />
                        </div>
                        <div class="w-10 text-right text-xs tabular-nums text-muted-foreground">{{ row.passRate }}%</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_pass_rate_by_level')">
                <div v-if="passRateByLevel.length === 0" class="h-[140px]">
                    <Empty :message="$t('dashboard.academic_no_department_pass_rates')" />
                </div>
                <div v-else class="flex flex-col gap-1.5">
                    <div v-for="row in passRateByLevel" :key="row.levelId" class="flex items-center gap-2">
                        <div class="w-28 shrink-0 truncate text-xs text-foreground">{{ row.levelName }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-1.5 rounded-sm"
                                :class="passRateBarClass(row.passRate)"
                                :style="{ width: `${row.barPercent}%` }"
                            />
                        </div>
                        <div class="w-10 text-right text-xs tabular-nums text-muted-foreground">{{ row.passRate }}%</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_pass_rate_by_course')">
                <div v-if="passRateByCourse.length === 0" class="h-[140px]">
                    <Empty :message="$t('dashboard.academic_no_department_pass_rates')" />
                </div>
                <div v-else class="flex flex-col gap-1.5">
                    <div v-for="row in passRateByCourse" :key="row.courseId" class="flex items-center gap-2">
                        <div class="w-28 shrink-0 truncate text-xs text-foreground">{{ row.courseName }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-1.5 rounded-sm"
                                :class="passRateBarClass(row.passRate)"
                                :style="{ width: `${row.barPercent}%` }"
                            />
                        </div>
                        <div class="w-10 text-right text-xs tabular-nums text-muted-foreground">{{ row.passRate }}%</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard
                v-if="topPerformingCourses.length > 0"
                compact
                :title="$t('dashboard.academic_top_performing_courses')"
            >
                <div class="flex flex-col gap-1.5">
                    <div v-for="row in topPerformingCourses" :key="`top-course-${row.courseId}`" class="flex items-center gap-2">
                        <div class="w-28 shrink-0 truncate text-xs text-foreground">{{ row.courseName }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-1.5 rounded-sm"
                                :class="passRateBarClass(row.passRate)"
                                :style="{ width: `${row.barPercent}%` }"
                            />
                        </div>
                        <div class="w-10 text-right text-xs tabular-nums text-muted-foreground">{{ row.passRate }}%</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard
                v-if="bottomPerformingCourses.length > 0"
                compact
                :title="$t('dashboard.academic_bottom_performing_courses')"
            >
                <div class="flex flex-col gap-1.5">
                    <div
                        v-for="row in bottomPerformingCourses"
                        :key="`bottom-course-${row.courseId}`"
                        class="flex items-center gap-2"
                    >
                        <div class="w-28 shrink-0 truncate text-xs text-foreground">{{ row.courseName }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div
                                class="h-1.5 rounded-sm"
                                :class="passRateBarClass(row.passRate)"
                                :style="{ width: `${row.barPercent}%` }"
                            />
                        </div>
                        <div class="w-10 text-right text-xs tabular-nums text-muted-foreground">{{ row.passRate }}%</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_missing_marks_by_department')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[42%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.overview_departments') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_expected') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_incomplete') }}
                            </th>
                            <th class="w-[22%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_rate') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="missingMarksByDepartment.length === 0">
                            <tr>
                                <td colspan="4" class="py-3 text-center text-sm text-muted-foreground">
                                    {{ $t('dashboard.academic_no_missing_marks_data') }}
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-for="row in missingMarksByDepartment"
                            :key="row.departmentId"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="truncate py-1.5 text-foreground">{{ row.departmentName }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.expected }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.incomplete }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="incompleteBadgeClass(row.rate)"
                                >
                                    {{ row.rate }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_missing_marks_by_level')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[42%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">Level</th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_expected') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_incomplete') }}
                            </th>
                            <th class="w-[22%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_rate') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="missingMarksByLevel.length === 0">
                            <tr>
                                <td colspan="4" class="py-3 text-center text-sm text-muted-foreground">
                                    {{ $t('dashboard.academic_no_missing_marks_data') }}
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-for="row in missingMarksByLevel"
                            :key="row.levelId"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="truncate py-1.5 text-foreground">{{ row.levelName }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.expected }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.incomplete }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="incompleteBadgeClass(row.rate)"
                                >
                                    {{ row.rate }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_missing_marks_by_course')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[42%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">Course</th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_expected') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_incomplete') }}
                            </th>
                            <th class="w-[22%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_rate') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="missingMarksByCourse.length === 0">
                            <tr>
                                <td colspan="4" class="py-3 text-center text-sm text-muted-foreground">
                                    {{ $t('dashboard.academic_no_missing_marks_data') }}
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-for="row in missingMarksByCourse"
                            :key="row.courseId"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="truncate py-1.5 text-foreground">{{ row.courseName }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.expected }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.incomplete }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="incompleteBadgeClass(row.rate)"
                                >
                                    {{ row.rate }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_missing_marks_by_module')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[42%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_module') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_expected') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_incomplete') }}
                            </th>
                            <th class="w-[22%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_rate') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="missingMarksByModule.length === 0">
                            <tr>
                                <td colspan="4" class="py-3 text-center text-sm text-muted-foreground">
                                    {{ $t('dashboard.academic_no_missing_marks_data') }}
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-for="row in missingMarksByModule"
                            :key="row.moduleId"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="truncate py-1.5 text-foreground">{{ row.moduleName }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.expected }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.incomplete }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="incompleteBadgeClass(row.rate)"
                                >
                                    {{ row.rate }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_module_failure_hotspots')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[42%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_module') }}
                            </th>
                            <th class="w-[18%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_enrolled') }}
                            </th>
                            <th class="w-[14%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_failing') }}
                            </th>
                            <th class="w-[26%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_rate') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="moduleFailureHotspots.length === 0">
                            <tr class="border-b border-border/60 last:border-0">
                                <td class="truncate py-1.5 text-foreground">{{ notAvailable }}</td>
                                <td class="py-1.5 text-foreground">{{ notAvailable }}</td>
                                <td class="py-1.5 text-foreground">{{ notAvailable }}</td>
                                <td class="py-1.5 text-foreground">{{ notAvailable }}</td>
                            </tr>
                        </template>
                        <tr
                            v-for="row in moduleFailureHotspots"
                            :key="row.moduleId"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="truncate py-1.5 text-foreground">{{ row.moduleName }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.enrolled }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.failing }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="failureBadgeClass(row.rate)"
                                >
                                    {{ row.rate }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard compact :title="$t('dashboard.academic_lecturer_marking_stats')">
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[30%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_lecturer') }}
                            </th>
                            <th class="w-[14%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_incomplete') }}
                            </th>
                            <th class="w-[14%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_rate') }}
                            </th>
                            <th class="w-[14%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_failing') }}
                            </th>
                            <th class="w-[14%] border-b border-border/60 pb-1.5 text-xs font-medium text-muted-foreground">
                                {{ $t('dashboard.academic_classes') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="lecturerMarkingStats.length === 0">
                            <tr class="border-b border-border/60 last:border-0">
                                <td colspan="5" class="py-3 text-center text-sm text-muted-foreground">
                                    {{ $t('dashboard.academic_no_lecturer_stats') }}
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-for="row in lecturerMarkingStats"
                            :key="row.staffId"
                            class="border-b border-border/60 last:border-0"
                        >
                            <td class="truncate py-1.5 text-foreground">{{ row.lecturerName }}</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.incomplete }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="incompleteBadgeClass(row.incompleteRate)"
                                >
                                    {{ row.incompleteRate }}%
                                </span>
                            </td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.failRate }}%</td>
                            <td class="py-1.5 tabular-nums text-foreground">{{ row.classesCount }}</td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>
        </div>
    </div>
</template>
