<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import MetricCard from '@/pages/dashboard/components/MetricCard.vue';
import DashboardCard from '@/pages/dashboard/components/DashboardCard.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import type { LecturerDashboard } from '@/types/lecturer';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { useUtils } from '@/composables/core/useUtils';
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BookOpen,
    ClipboardList,
    GraduationCap,
    TrendingDown,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

interface Props {
    dashboard: LecturerDashboard;
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    dashboardTitle: string;
}

const props = defineProps<Props>();
const { navigateTo } = useUtils();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { title: props.dashboardTitle },
];

const notAvailable = computed(() => trans('dashboard.overview_not_available'));

const formatRate = (value: number | null): string =>
    value === null ? notAvailable.value : `${value}%`;

const formatCount = (value: number | null | undefined): string =>
    value === null || value === undefined ? notAvailable.value : value.toLocaleString();

const formatMark = (value: number | null | undefined): string =>
    value === null || value === undefined ? notAvailable.value : String(value);

const alertDotClass = (severity: string): string => {
    if (severity === 'critical') {
        return 'bg-rose-500';
    }

    if (severity === 'warning') {
        return 'bg-amber-500';
    }

    return 'bg-sky-500';
};

const openAction = (url: string | null, enabled: boolean): void => {
    if (!enabled || !url) {
        return;
    }

    navigateTo(url);
};
</script>

<template>
    <Head :title="dashboardTitle" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <ComponentHeader
                :header-title="dashboardTitle"
                :description="academicContextSubtitle"
            />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <MetricCard
                    :title="$t('dashboard.lecturer_attendance')"
                    :value="notAvailable"
                    :subtext="$t('dashboard.lecturer_attendance_unavailable')"
                    trend="neutral"
                    compact
                >
                    <template #icon><Users class="h-4 w-4" /></template>
                </MetricCard>
                <MetricCard
                    :title="$t('dashboard.lecturer_pass_rate')"
                    :value="formatRate(dashboard.summary.passRate)"
                    :subtext="
                        dashboard.summary.markCompletionRate === null
                            ? notAvailable
                            : $t('dashboard.lecturer_mark_completion_subtext', {
                                  rate: dashboard.summary.markCompletionRate,
                              })
                    "
                    trend="neutral"
                    compact
                >
                    <template #icon><GraduationCap class="h-4 w-4" /></template>
                </MetricCard>
                <MetricCard
                    :title="$t('dashboard.lecturer_average')"
                    :value="formatMark(dashboard.summary.averageMark)"
                    :subtext="
                        dashboard.summary.averageMark === null
                            ? notAvailable
                            : $t('dashboard.lecturer_mark_completion_subtext', {
                                  rate: dashboard.summary.markCompletionRate ?? 0,
                              })
                    "
                    trend="neutral"
                    compact
                >
                    <template #icon><TrendingUp class="h-4 w-4" /></template>
                </MetricCard>
                <MetricCard
                    :title="$t('dashboard.lecturer_modules')"
                    :value="formatCount(dashboard.summary.modulesCount)"
                    :subtext="
                        $t('dashboard.lecturer_classes_count', {
                            count: dashboard.summary.classesCount,
                        })
                    "
                    trend="neutral"
                    compact
                >
                    <template #icon><BookOpen class="h-4 w-4" /></template>
                </MetricCard>
                <MetricCard
                    :title="$t('dashboard.lecturer_at_risk')"
                    :value="formatCount(dashboard.summary.atRiskStudentCount)"
                    :subtext="$t('dashboard.overview_at_risk_subtext')"
                    trend="warning"
                    compact
                >
                    <template #icon><AlertTriangle class="h-4 w-4" /></template>
                </MetricCard>
                <MetricCard
                    :title="$t('dashboard.lecturer_missing_coursework')"
                    :value="formatCount(dashboard.summary.missingCourseWorkCount)"
                    :subtext="
                        dashboard.summary.markCompletionRate === null
                            ? notAvailable
                            : $t('dashboard.lecturer_mark_completion_subtext', {
                                  rate: dashboard.summary.markCompletionRate,
                              })
                    "
                    trend="warning"
                    compact
                >
                    <template #icon><ClipboardList class="h-4 w-4" /></template>
                </MetricCard>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <DashboardCard :title="$t('dashboard.lecturer_priority_alerts')">
                    <Empty
                        v-if="dashboard.priorityAlerts.length === 0"
                        :message="$t('dashboard.lecturer_no_alerts')"
                    />
                    <div v-else class="flex flex-col gap-0">
                        <div
                            v-for="(alert, index) in dashboard.priorityAlerts"
                            :key="index"
                            class="flex gap-3 border-b border-gray-100 py-2 last:border-0"
                        >
                            <div
                                class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                :class="alertDotClass(alert.severity)"
                            ></div>
                            <div class="text-sm leading-snug text-gray-900">{{ alert.message }}</div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard :title="$t('dashboard.lecturer_quick_actions')">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button
                            v-for="action in dashboard.quickActions"
                            :key="action.key"
                            type="button"
                            class="rounded-lg border border-border px-3 py-2.5 text-left text-sm font-medium transition-colors"
                            :class="
                                action.enabled
                                    ? 'bg-card hover:bg-muted/40'
                                    : 'cursor-not-allowed bg-muted/30 text-muted-foreground'
                            "
                            :disabled="!action.enabled"
                            @click="openAction(action.url, action.enabled)"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </DashboardCard>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <DashboardCard :title="$t('dashboard.lecturer_top_performing')">
                    <Empty
                        v-if="dashboard.topPerformingStudents.length === 0"
                        :message="$t('dashboard.lecturer_no_students')"
                    />
                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="student in dashboard.topPerformingStudents"
                            :key="student.studentEnrolmentId"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="text-foreground">{{ student.studentName }}</span>
                            <span class="font-medium text-emerald-600">{{
                                formatMark(student.averageMark)
                            }}</span>
                        </li>
                    </ul>
                </DashboardCard>

                <DashboardCard :title="$t('dashboard.lecturer_low_performing')">
                    <Empty
                        v-if="dashboard.lowPerformingStudents.length === 0"
                        :message="$t('dashboard.lecturer_no_students')"
                    />
                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="student in dashboard.lowPerformingStudents"
                            :key="student.studentEnrolmentId"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="text-foreground">{{ student.studentName }}</span>
                            <span class="inline-flex items-center gap-1 font-medium text-rose-600">
                                <TrendingDown class="h-3.5 w-3.5" />
                                {{ formatMark(student.averageMark) }}
                            </span>
                        </li>
                    </ul>
                </DashboardCard>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <DashboardCard :title="$t('dashboard.lecturer_risky_students')">
                    <Empty
                        v-if="dashboard.riskyStudents.length === 0"
                        :message="$t('dashboard.lecturer_no_students')"
                    />
                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="student in dashboard.riskyStudents"
                            :key="student.studentEnrolmentId"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="text-foreground">{{ student.studentName }}</span>
                            <span class="text-amber-600">{{
                                $t('dashboard.lecturer_failing_count', {
                                    count: student.failCount ?? 0,
                                })
                            }}</span>
                        </li>
                    </ul>
                </DashboardCard>

                <DashboardCard :title="$t('dashboard.lecturer_missing_coursework')">
                    <Empty
                        v-if="dashboard.missingCourseWork.length === 0"
                        :message="$t('dashboard.lecturer_no_missing')"
                    />
                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="row in dashboard.missingCourseWork"
                            :key="`${row.academicCalendarClassId}-${row.moduleId}`"
                            class="flex items-start justify-between gap-3 py-2 text-sm"
                        >
                            <div>
                                <div class="font-medium text-foreground">{{ row.moduleName }}</div>
                                <div class="text-muted-foreground">{{ row.className }}</div>
                            </div>
                            <span class="shrink-0 text-amber-600">{{
                                $t('dashboard.lecturer_incomplete_count', {
                                    count: row.incompleteCount,
                                })
                            }}</span>
                        </li>
                    </ul>
                </DashboardCard>
            </div>

            <DashboardCard :title="$t('dashboard.lecturer_my_modules')">
                <Empty
                    v-if="dashboard.modules.length === 0"
                    :message="$t('dashboard.lecturer_no_modules')"
                />
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border text-muted-foreground">
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.class', 2) }}</th>
                                <th class="py-2 pr-3 font-medium">{{ $t('dashboard.lecturer_pass_rate') }}</th>
                                <th class="py-2 font-medium">{{ $t('dashboard.lecturer_average') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="module in dashboard.modules"
                                :key="module.moduleId"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="py-2 pr-3">
                                    <div class="font-medium text-foreground">{{ module.moduleName }}</div>
                                    <div class="text-xs text-muted-foreground">{{ module.moduleCode }}</div>
                                </td>
                                <td class="py-2 pr-3">{{ module.classesCount }}</td>
                                <td class="py-2 pr-3">{{ formatRate(module.passRate) }}</td>
                                <td class="py-2">{{ formatMark(module.averageMark) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardCard>
        </div>
    </PageContainer>
</template>
