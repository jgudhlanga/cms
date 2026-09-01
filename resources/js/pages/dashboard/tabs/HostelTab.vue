<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
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
import MetricCard from '../components/MetricCard.vue';

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
        { key: 'male', label: trans_choice('general.male', 1), count: genderSplit.male, percent: Math.round((genderSplit.male / total) * 100), barClass: 'bg-blue-500' },
        { key: 'female', label: trans_choice('general.female', 1), count: genderSplit.female, percent: Math.round((genderSplit.female / total) * 100), barClass: 'bg-pink-500' },
        ...(genderSplit.other > 0
            ? [{ key: 'other', label: trans('dashboard.hostel_other_gender'), count: genderSplit.other, percent: Math.round((genderSplit.other / total) * 100), barClass: 'bg-violet-500' }]
            : []),
    ];
});

const occupancyBarClass = (rate: number): string => {
    if (rate <= 70) return 'bg-emerald-500';
    if (rate <= 90) return 'bg-amber-500';

    return 'bg-rose-500';
};

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
    <div class="mt-4 flex flex-col gap-3">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-7">
            <MetricCard
                compact
                accent="bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                :title="$t('hms.stat_blocks')"
                :value="summary.blocks"
                :subtext="capacitySubtext"
                trend="neutral"
            >
                <template #icon><Building class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                :title="$t('hms.stat_total_capacity')"
                :value="summary.totalCapacity"
                :subtext="occupancySubtext"
                trend="neutral"
            >
                <template #icon><Bed class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                :title="$t('hms.stat_rooms')"
                :value="summary.totalRooms"
                :subtext="$t('dashboard.hostel_vacant_rooms', { count: String(summary.vacantRooms) })"
                trend="neutral"
            >
                <template #icon><DoorOpen class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                :title="$t('hms.stat_occupied_beds')"
                :value="summary.occupiedBeds"
                :subtext="occupancySubtext"
                :trend="summary.occupancyRate > 90 ? 'warning' : 'neutral'"
            >
                <template #icon><UserCheck class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                :title="$t('hms.stat_disabled_students')"
                :value="summary.disabledStudents"
                :subtext="$t('dashboard.student_enrolled')"
                trend="neutral"
            >
                <template #icon><Accessibility class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300"
                :title="$t('hms.max_occupancy')"
                :value="summary.totalMaxOccupancy"
                :subtext="$t('dashboard.hostel_across_blocks', { count: String(summary.blocks) })"
                trend="neutral"
            >
                <template #icon><Bed class="h-3.5 w-3.5" /></template>
            </MetricCard>
            <MetricCard
                compact
                accent="bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                :title="$t('hms.room_status_vacant')"
                :value="summary.vacantRooms"
                :subtext="$t('dashboard.hostel_beds_available', { count: String(summary.availableBeds) })"
                trend="neutral"
            >
                <template #icon><LayoutGrid class="h-3.5 w-3.5" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.hostel_occupancy_by_block')">
                <Empty v-if="blocks.length === 0" :message="$t('hms.no_hostels_found')" />
                <div v-else class="mt-1 flex flex-col gap-0">
                    <div
                        v-for="block in blocks"
                        :key="block.id"
                        class="flex items-center gap-3 border-b border-border/60 py-2.5 last:border-0"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :class="blockIconClass(block)"
                        >
                            <AlertTriangle v-if="block.maintenanceRooms > 0" class="h-4 w-4" />
                            <Building v-else class="h-4 w-4" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[13px] font-medium text-foreground">{{ blockTitle(block) }}</div>
                            <div
                                class="text-xs"
                                :class="block.maintenanceRooms > 0 ? 'text-rose-600' : 'text-muted-foreground'"
                            >
                                {{ block.subtitle }}
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-sm bg-muted">
                                <div
                                    class="h-1.5 rounded-sm"
                                    :class="blockBarClass(block)"
                                    :style="{ width: `${block.occupancyRate}%` }"
                                />
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <div class="text-[13px] font-medium tabular-nums text-foreground">{{ block.occupied }}/{{ block.capacity }}</div>
                            <div class="mt-0.5 text-xs text-muted-foreground">
                                <span
                                    class="inline-block rounded-full px-2 py-0.5 text-[10px]"
                                    :class="occupancyBadgeClass(block.occupancyRate)"
                                >
                                    {{ block.occupancyRate }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.hostel_gender_split')">
                <Empty v-if="genderTotal === 0" :message="$t('dashboard.hostel_no_residents')" />
                <div v-else class="mt-2 flex flex-col gap-2">
                    <div v-for="row in genderRows" :key="row.key" class="flex items-center gap-2">
                        <div class="w-24 shrink-0 text-xs text-foreground">{{ row.label }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-muted">
                            <div class="h-1.5 rounded-sm" :class="row.barClass" :style="{ width: `${row.percent}%` }" />
                        </div>
                        <div class="w-8 text-right text-xs tabular-nums text-muted-foreground">{{ row.count }}</div>
                    </div>
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <DashboardCard :title="$t('dashboard.hostel_maintenance_facilities')">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_open_queries') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ queryStats.open }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_high_priority_queries') }}</span>
                        <span class="text-xs font-medium text-foreground">
                            <span
                                v-if="queryStats.highPriority > 0"
                                class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                            >
                                {{ queryStats.highPriority }}
                            </span>
                            <span v-else>{{ queryStats.highPriority }}</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_in_progress_queries') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ queryStats.inProgress }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_resolved_this_month') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ queryStats.resolvedThisMonth }}</span>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.hostel_fees_payments')">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_fully_paid') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ applicationStats.paid + applicationStats.approved }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_partial_payment') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ applicationStats.partiallyPaid }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_awaiting_payment') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ applicationStats.awaitingPayment }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_pending_applications') }}</span>
                        <span class="text-xs font-medium text-foreground">{{ applicationStats.pending }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-border/60 py-1.5 last:border-0">
                        <span class="text-xs text-muted-foreground">{{ $t('dashboard.hostel_declined_applications') }}</span>
                        <span class="text-xs font-medium text-foreground">
                            <span
                                v-if="applicationStats.declined > 0"
                                class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                            >
                                {{ applicationStats.declined }}
                            </span>
                            <span v-else>{{ applicationStats.declined }}</span>
                        </span>
                    </div>
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
