<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import type { StaffDashboard } from '@/types/dashboard';
import { Chart, registerables } from 'chart.js';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { Clock, UserCheck, Users, UserX } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

Chart.register(...registerables);

interface Props {
    staffDashboard: StaffDashboard;
}

const props = defineProps<Props>();

const { summary, lecturerRatios, categoryBreakdown, academicGenderSplit, overCapacityRooms, attendanceTrend } =
    props.staffDashboard;

const notAvailable = computed(() => trans('dashboard.staff_not_available'));

const totalStaffSubtext = computed(() =>
    trans('dashboard.staff_academic_admin_subtext', {
        academic: String(summary.academicCount),
        admin: String(summary.adminCount),
    }),
);

const metricValue = (value: number | null): string | number => (value === null ? notAvailable.value : value);

const metricSubtext = (value: number | null, fallback: string): string =>
    value === null ? notAvailable.value : fallback;

const ratioBarClass = (ratio: number | null): string => {
    if (ratio === null) return 'bg-gray-300';
    if (ratio >= 25) return 'bg-rose-500';
    if (ratio >= 18) return 'bg-orange-400';

    return 'bg-emerald-500';
};

const categoryChart = ref<HTMLCanvasElement | null>(null);
let categoryChartInstance: Chart | null = null;

const segmentColors: Record<string, string> = {
    academic: 'rgba(59, 130, 246, 0.8)',
    admin: 'rgba(99, 102, 241, 0.8)',
    support: 'rgba(156, 163, 175, 0.8)',
};

const categoryChartData = computed(() => {
    const segments = categoryBreakdown.segments;

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

const genderTotal = computed(
    () => academicGenderSplit.male + academicGenderSplit.female + academicGenderSplit.other,
);

const genderRows = computed(() => {
    const total = genderTotal.value || 1;

    return [
        {
            key: 'male',
            label: trans_choice('general.male', 1),
            count: academicGenderSplit.male,
            percent: Math.round((academicGenderSplit.male / total) * 100),
            barClass: 'bg-blue-500',
        },
        {
            key: 'female',
            label: trans_choice('general.female', 1),
            count: academicGenderSplit.female,
            percent: Math.round((academicGenderSplit.female / total) * 100),
            barClass: 'bg-pink-500',
        },
        ...(academicGenderSplit.other > 0
            ? [
                  {
                      key: 'other',
                      label: trans('dashboard.hostel_other_gender'),
                      count: academicGenderSplit.other,
                      percent: Math.round((academicGenderSplit.other / total) * 100),
                      barClass: 'bg-violet-500',
                  },
              ]
            : []),
    ];
});

const formatNullableCount = (value: number | null): string => (value === null ? notAvailable.value : String(value));

const initCategoryChart = () => {
    if (!categoryChart.value || categoryBreakdown.segments.length === 0) {
        if (categoryChartInstance) {
            categoryChartInstance.destroy();
            categoryChartInstance = null;
        }

        return;
    }

    if (categoryChartInstance) {
        categoryChartInstance.destroy();
    }

    categoryChartInstance = new Chart(categoryChart.value, {
        type: 'doughnut',
        data: { ...categoryChartData.value },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '60%',
        },
    });
};

onMounted(() => {
    initCategoryChart();
});

watch(
    () => categoryBreakdown.segments,
    () => {
        initCategoryChart();
    },
    { deep: true },
);
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
                :title="$t('dashboard.staff_total_staff')"
                :value="summary.totalStaff"
                :subtext="totalStaffSubtext"
                trend="neutral"
            >
                <template #icon><Users class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.staff_present_today')"
                :value="metricValue(summary.presentToday)"
                :subtext="metricSubtext(summary.presentToday, '')"
                trend="neutral"
            >
                <template #icon><UserCheck class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.staff_on_leave_today')"
                :value="metricValue(summary.onLeaveToday)"
                :subtext="metricSubtext(summary.onLeaveToday, '')"
                trend="neutral"
            >
                <template #icon><UserX class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.staff_unfilled_sessions')"
                :value="metricValue(summary.unfilledSessions)"
                :subtext="metricSubtext(summary.unfilledSessions, $t('dashboard.staff_unfilled_sessions_subtext'))"
                :trend="summary.unfilledSessions === null ? 'neutral' : 'warning'"
            >
                <template #icon><Clock class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.staff_lecturer_ratio_by_department')">
                <Empty v-if="lecturerRatios.length === 0" :message="$t('dashboard.staff_no_department_ratios')" />
                <div v-else class="mt-1 flex flex-col gap-2">
                    <div v-for="row in lecturerRatios" :key="row.departmentId" class="flex items-center gap-2">
                        <div class="w-32 shrink-0 truncate text-xs text-gray-900">{{ row.departmentName }}</div>
                        <div class="h-2 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div
                                class="h-2 rounded-sm"
                                :class="ratioBarClass(row.ratio)"
                                :style="{ width: `${row.barPercent}%` }"
                            />
                        </div>
                        <div class="w-12 text-right text-[11px] text-gray-500">{{ row.ratioLabel }}</div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.staff_breakdown_by_category')">
                <Empty v-if="categoryBreakdown.segments.length === 0" :message="$t('dashboard.staff_not_available')" />
                <div v-else class="mb-2 h-[160px] w-full">
                    <canvas ref="categoryChart"></canvas>
                </div>
                <div v-if="categoryBreakdown.segments.length > 0" class="mt-2 mb-4 flex flex-wrap gap-3">
                    <div
                        v-for="segment in categoryBreakdown.segments"
                        :key="segment.key"
                        class="flex items-center gap-1.5 text-xs text-gray-500"
                    >
                        <div class="h-2.5 w-2.5 rounded-sm" :class="segment.color"></div>
                        {{ segment.label }} {{ segment.percent }}%
                    </div>
                </div>

                <div class="flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.staff_full_time_lecturers') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ categoryBreakdown.fullTimeLecturers }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.staff_part_time_lecturers') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ categoryBreakdown.partTimeLecturers }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.staff_postgrad_qualified') }}</span>
                        <span class="text-xs font-medium text-gray-900">
                            {{ formatNullableCount(categoryBreakdown.postgradQualified) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.staff_on_study_leave') }}</span>
                        <span class="text-xs font-medium text-gray-900">
                            {{ formatNullableCount(categoryBreakdown.onStudyLeave) }}
                        </span>
                    </div>
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.staff_gender_split_academic')">
                <Empty v-if="genderTotal === 0" :message="$t('dashboard.staff_no_academic_staff')" />
                <template v-else>
                    <div v-for="row in genderRows" :key="row.key" class="mt-1 mb-2 flex items-center gap-2">
                        <div class="w-24 shrink-0 text-xs text-gray-900">{{ row.label }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-1.5 rounded-sm" :class="row.barClass" :style="{ width: `${row.percent}%` }" />
                        </div>
                        <div class="w-8 text-right text-xs text-gray-500">{{ row.percent }}%</div>
                    </div>
                </template>

                <div class="mt-4 mb-2 text-xs font-medium tracking-wider text-gray-500 uppercase">
                    {{ $t('dashboard.staff_workshops_over_capacity') }}
                </div>
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[36%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">
                                {{ $t('dashboard.staff_room') }}
                            </th>
                            <th class="w-[32%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">
                                {{ $t('dashboard.staff_department') }}
                            </th>
                            <th class="w-[16%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">
                                {{ $t('dashboard.staff_capacity') }}
                            </th>
                            <th class="w-[16%] border-b border-gray-100 pb-2 text-xs font-medium text-gray-500">
                                {{ $t('dashboard.staff_current_occupancy') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="overCapacityRooms.length === 0">
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="truncate py-2 text-gray-900">{{ notAvailable }}</td>
                                <td class="py-2 text-gray-900">{{ notAvailable }}</td>
                                <td class="py-2 text-gray-900">{{ notAvailable }}</td>
                                <td class="py-2 text-gray-900">{{ notAvailable }}</td>
                            </tr>
                        </template>
                        <tr
                            v-for="(room, index) in overCapacityRooms"
                            :key="`${room.room}-${index}`"
                            class="border-b border-gray-100 last:border-0"
                        >
                            <td class="truncate py-2 text-gray-900">{{ room.room }}</td>
                            <td class="py-2 text-gray-900">{{ room.department }}</td>
                            <td class="py-2 text-gray-900">{{ room.capacity }}</td>
                            <td class="py-2">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="
                                        room.severity === 'critical'
                                            ? 'bg-rose-100 text-rose-700'
                                            : 'bg-amber-100 text-amber-700'
                                    "
                                >
                                    {{ room.currentOccupancy }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.staff_attendance_trend')">
                <div
                    v-if="attendanceTrend === null"
                    class="flex h-[185px] w-full items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-500"
                >
                    {{ $t('dashboard.staff_attendance_not_available') }}
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
