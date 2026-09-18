<script setup lang="ts">
import CardEmpty from '../components/CardEmpty.vue';
import type { StaffDashboard } from '@/types/dashboard';
import { Chart, registerables } from 'chart.js';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import DashboardCard from '../components/DashboardCard.vue';
import DataRow from '../components/DataRow.vue';
import MetricCard from '../components/MetricCard.vue';
import StatLine from '../components/StatLine.vue';
import { chartPalette, doughnutOptions } from '../components/chartTheme';
import type { Tone } from '../components/tones';

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

const ratioTone = (ratio: number | null): Tone => {
    if (ratio === null) return 'slate';
    if (ratio >= 25) return 'rose';
    if (ratio >= 18) return 'orange';

    return 'emerald';
};

const categoryChart = ref<HTMLCanvasElement | null>(null);
let categoryChartInstance: Chart | null = null;

const categoryChartData = computed(() => {
    const segments = categoryBreakdown.segments;

    return {
        labels: segments.map((segment) => `${segment.label} · ${segment.percent}%`),
        datasets: [
            {
                data: segments.map((segment) => segment.count),
                backgroundColor: segments.map((_, index) => chartPalette[index % chartPalette.length]),
                borderWidth: 0,
                hoverOffset: 4,
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
            tone: 'blue' as Tone,
        },
        {
            key: 'female',
            label: trans_choice('general.female', 1),
            count: academicGenderSplit.female,
            percent: Math.round((academicGenderSplit.female / total) * 100),
            tone: 'pink' as Tone,
        },
        ...(academicGenderSplit.other > 0
            ? [
                  {
                      key: 'other',
                      label: trans('dashboard.hostel_other_gender'),
                      count: academicGenderSplit.other,
                      percent: Math.round((academicGenderSplit.other / total) * 100),
                      tone: 'violet' as Tone,
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
        options: doughnutOptions('right'),
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
    <div class="mt-3 flex flex-col gap-2.5">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
            <MetricCard
                tone="violet"
                :title="$t('dashboard.staff_total_staff')"
                :value="summary.totalStaff"
                :subtext="totalStaffSubtext"
                trend="neutral"
            >
                <template #icon><Users class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.staff_lecturer_ratio_by_department')">
                <CardEmpty v-if="lecturerRatios.length === 0" :message="$t('dashboard.staff_no_department_ratios')" />
                <div v-else class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in lecturerRatios"
                        :key="row.departmentId"
                        :label="row.departmentName"
                        :value="row.ratioLabel"
                        :percent="row.barPercent"
                        :tone="ratioTone(row.ratio)"
                        label-width-class="w-32"
                    />
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.staff_breakdown_by_category')">
                <CardEmpty v-if="categoryBreakdown.segments.length === 0" :message="$t('dashboard.staff_not_available')" />
                <div v-else class="mb-2 h-40 w-full">
                    <canvas ref="categoryChart"></canvas>
                </div>

                <div class="flex flex-col gap-0.5 border-t border-border/50 pt-1.5">
                    <StatLine
                        :label="$t('dashboard.staff_full_time_lecturers')"
                        :value="categoryBreakdown.fullTimeLecturers"
                    />
                    <StatLine
                        :label="$t('dashboard.staff_part_time_lecturers')"
                        :value="categoryBreakdown.partTimeLecturers"
                    />
                    <StatLine
                        :label="$t('dashboard.staff_postgrad_qualified')"
                        :value="formatNullableCount(categoryBreakdown.postgradQualified)"
                    />
                    <StatLine
                        :label="$t('dashboard.staff_on_study_leave')"
                        :value="formatNullableCount(categoryBreakdown.onStudyLeave)"
                    />
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.staff_gender_split_academic')">
                <CardEmpty v-if="genderTotal === 0" :message="$t('dashboard.staff_no_academic_staff')" />
                <div v-else class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in genderRows"
                        :key="row.key"
                        :label="row.label"
                        :value="`${row.percent}%`"
                        :percent="row.percent"
                        :tone="row.tone"
                        label-width-class="w-24"
                        value-width-class="w-10"
                    />
                </div>

                <div class="mt-3 mb-1.5 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                    {{ $t('dashboard.staff_workshops_over_capacity') }}
                </div>
                <table class="w-full table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            <th class="w-[36%] border-b border-border/60 pb-1.5 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                                {{ $t('dashboard.staff_room') }}
                            </th>
                            <th class="w-[32%] border-b border-border/60 pb-1.5 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                                {{ $t('dashboard.staff_department') }}
                            </th>
                            <th class="w-[16%] border-b border-border/60 pb-1.5 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                                {{ $t('dashboard.staff_capacity') }}
                            </th>
                            <th class="w-[16%] border-b border-border/60 pb-1.5 text-[10px] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
                                {{ $t('dashboard.staff_current_occupancy') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="overCapacityRooms.length === 0">
                            <tr class="border-b border-border/60 transition-colors last:border-0 hover:bg-muted/40">
                                <td class="truncate py-1.5 text-[11px] text-foreground">{{ notAvailable }}</td>
                                <td class="py-1.5 text-[11px] text-foreground">{{ notAvailable }}</td>
                                <td class="py-1.5 text-[11px] text-foreground">{{ notAvailable }}</td>
                                <td class="py-1.5 text-[11px] text-foreground">{{ notAvailable }}</td>
                            </tr>
                        </template>
                        <tr
                            v-for="(room, index) in overCapacityRooms"
                            :key="`${room.room}-${index}`"
                            class="border-b border-border/60 transition-colors last:border-0 hover:bg-muted/40"
                        >
                            <td class="truncate py-1.5 text-[11px] text-foreground">{{ room.room }}</td>
                            <td class="py-1.5 text-[11px] text-foreground">{{ room.department }}</td>
                            <td class="py-1.5 text-[11px] tabular-nums text-foreground">{{ room.capacity }}</td>
                            <td class="py-1.5">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="
                                        room.severity === 'critical'
                                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
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
                    class="flex h-40 w-full items-center justify-center rounded-lg border border-dashed border-border/70 bg-muted/20 text-[11px] text-muted-foreground"
                >
                    {{ $t('dashboard.staff_attendance_not_available') }}
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
