<script setup lang="ts">
import CardEmpty from '../components/CardEmpty.vue';
import type { HostelDashboard, HostelDashboardBlock } from '@/types/dashboard';
import {
    Accessibility,
    AlertTriangle,
    Bed,
    Building,
    DoorOpen,
    LayoutGrid,
    UserCheck,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { trans, trans_choice } from 'laravel-vue-i18n';
import DashboardCard from '../components/DashboardCard.vue';
import DataRow from '../components/DataRow.vue';
import MetricCard from '../components/MetricCard.vue';
import StatLine from '../components/StatLine.vue';
import type { Tone } from '../components/tones';

interface Props {
    hostelDashboard: HostelDashboard;
}

const props = defineProps<Props>();

const { summary, blocks, genderSplit, queryStats, applicationStats } = props.hostelDashboard;

const capacitySubtext = computed(() =>
    trans('dashboard.hostel_across_blocks', { count: String(summary.blocks) }),
);

const occupancySubtext = computed(() =>
    trans('dashboard.hostel_occupancy_rate', { rate: String(summary.occupancyRate) }),
);

const genderTotal = computed(() => genderSplit.male + genderSplit.female + genderSplit.other);

const genderRows = computed(() => {
    const total = genderTotal.value || 1;

    return [
        { key: 'male', label: trans_choice('general.male', 1), count: genderSplit.male, percent: Math.round((genderSplit.male / total) * 100), tone: 'blue' as Tone },
        { key: 'female', label: trans_choice('general.female', 1), count: genderSplit.female, percent: Math.round((genderSplit.female / total) * 100), tone: 'pink' as Tone },
        ...(genderSplit.other > 0
            ? [{ key: 'other', label: trans('dashboard.hostel_other_gender'), count: genderSplit.other, percent: Math.round((genderSplit.other / total) * 100), tone: 'violet' as Tone }]
            : []),
    ];
});

const occupancyBadgeClass = (rate: number): string => {
    if (rate <= 70) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    if (rate <= 90) return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';

    return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300';
};

const blockIconClass = (block: HostelDashboardBlock): string => {
    if (block.maintenanceRooms > 0) {
        return 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
    }

    if (block.type === 'female') {
        return 'bg-pink-50 text-pink-700 dark:bg-pink-950 dark:text-pink-300';
    }

    if (block.type === 'male') {
        return 'bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-300';
    }

    return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
};

const blockTitle = (block: HostelDashboardBlock): string => {
    const typeLabel = block.type ? trans(`hms.type_${block.type}`) : '';

    return typeLabel ? `${block.name} — ${typeLabel}` : block.name;
};

const blockBarClass = (block: HostelDashboardBlock): string => {
    if (block.type === 'female') return 'bg-pink-500';
    if (block.type === 'male') return 'bg-blue-500';
    if (block.maintenanceRooms > 0) return 'bg-amber-500';

    return 'bg-emerald-500';
};
</script>

<template>
    <div class="mt-3 flex flex-col gap-2.5">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            <MetricCard
                tone="indigo"
                :title="$t('hms.stat_blocks')"
                :value="summary.blocks"
                :subtext="capacitySubtext"
                trend="neutral"
            >
                <template #icon><Building class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="emerald"
                :title="$t('hms.stat_total_capacity')"
                :value="summary.totalCapacity"
                :subtext="occupancySubtext"
                trend="neutral"
            >
                <template #icon><Bed class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="amber"
                :title="$t('hms.stat_rooms')"
                :value="summary.totalRooms"
                :subtext="$t('dashboard.hostel_vacant_rooms', { count: String(summary.vacantRooms) })"
                trend="neutral"
            >
                <template #icon><DoorOpen class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="rose"
                :title="$t('hms.stat_occupied_beds')"
                :value="summary.occupiedBeds"
                :subtext="occupancySubtext"
                :trend="summary.occupancyRate > 90 ? 'warning' : 'neutral'"
            >
                <template #icon><UserCheck class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="sky"
                :title="$t('hms.stat_disabled_students')"
                :value="summary.disabledStudents"
                :subtext="$t('dashboard.student_enrolled')"
                trend="neutral"
            >
                <template #icon><Accessibility class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="orange"
                :title="$t('hms.max_occupancy')"
                :value="summary.totalMaxOccupancy"
                :subtext="$t('dashboard.hostel_across_blocks', { count: String(summary.blocks) })"
                trend="neutral"
            >
                <template #icon><Bed class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                tone="blue"
                :title="$t('hms.room_status_vacant')"
                :value="summary.vacantRooms"
                :subtext="$t('dashboard.hostel_beds_available', { count: String(summary.availableBeds) })"
                trend="neutral"
            >
                <template #icon><LayoutGrid class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.hostel_occupancy_by_block')">
                <CardEmpty v-if="blocks.length === 0" :message="$t('hms.no_hostels_found')" />
                <div v-else class="flex flex-col gap-0.5">
                    <div
                        v-for="block in blocks"
                        :key="block.id"
                        class="flex items-center gap-2.5 rounded-md px-1.5 py-1.5 transition-colors hover:bg-muted/40"
                    >
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md"
                            :class="blockIconClass(block)"
                        >
                            <AlertTriangle v-if="block.maintenanceRooms > 0" class="h-3.5 w-3.5" />
                            <Building v-else class="h-3.5 w-3.5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-[11px] font-medium text-foreground">
                                    {{ blockTitle(block) }}
                                </span>
                                <span class="shrink-0 text-[11px] font-semibold tabular-nums tracking-tight text-foreground">
                                    {{ block.occupied }}/{{ block.capacity }}
                                </span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-1.5 rounded-full transition-all duration-500 ease-out"
                                    :class="blockBarClass(block)"
                                    :style="{ width: `${block.occupancyRate}%` }"
                                />
                            </div>
                            <div
                                class="mt-0.5 truncate text-[10px]"
                                :class="block.maintenanceRooms > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground'"
                            >
                                {{ block.subtitle }}
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums"
                            :class="occupancyBadgeClass(block.occupancyRate)"
                        >
                            {{ block.occupancyRate }}%
                        </span>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.hostel_gender_split')">
                <CardEmpty v-if="genderTotal === 0" :message="$t('dashboard.hostel_no_residents')" />
                <div v-else class="flex flex-col gap-2">
                    <DataRow
                        v-for="row in genderRows"
                        :key="row.key"
                        :label="row.label"
                        :value="row.count.toLocaleString()"
                        :percent="row.percent"
                        :tone="row.tone"
                        label-width-class="w-24"
                    />
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2">
            <DashboardCard :title="$t('dashboard.hostel_maintenance_facilities')">
                <div class="flex flex-col gap-0.5">
                    <StatLine :label="$t('dashboard.hostel_open_queries')" :value="queryStats.open" />
                    <StatLine
                        :label="$t('dashboard.hostel_high_priority_queries')"
                        :value="queryStats.highPriority"
                        :emphasis="queryStats.highPriority > 0 ? 'critical' : 'none'"
                    />
                    <StatLine :label="$t('dashboard.hostel_in_progress_queries')" :value="queryStats.inProgress" />
                    <StatLine
                        :label="$t('dashboard.hostel_resolved_this_month')"
                        :value="queryStats.resolvedThisMonth"
                        :emphasis="queryStats.resolvedThisMonth > 0 ? 'success' : 'none'"
                    />
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.hostel_fees_payments')">
                <div class="flex flex-col gap-0.5">
                    <StatLine
                        :label="$t('dashboard.hostel_fully_paid')"
                        :value="applicationStats.paid + applicationStats.approved"
                        emphasis="success"
                    />
                    <StatLine
                        :label="$t('dashboard.hostel_partial_payment')"
                        :value="applicationStats.partiallyPaid"
                        :emphasis="applicationStats.partiallyPaid > 0 ? 'warning' : 'none'"
                    />
                    <StatLine
                        :label="$t('dashboard.hostel_awaiting_payment')"
                        :value="applicationStats.awaitingPayment"
                        :emphasis="applicationStats.awaitingPayment > 0 ? 'warning' : 'none'"
                    />
                    <StatLine :label="$t('dashboard.hostel_pending_applications')" :value="applicationStats.pending" />
                    <StatLine
                        :label="$t('dashboard.hostel_declined_applications')"
                        :value="applicationStats.declined"
                        :emphasis="applicationStats.declined > 0 ? 'critical' : 'none'"
                    />
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
