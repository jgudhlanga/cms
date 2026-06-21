<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import type { HostelDashboard, HostelDashboardBlock } from '@/types/dashboard';
import { AlertTriangle, Bed, Building, Coins, DoorOpen, UserCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { trans, trans_choice } from 'laravel-vue-i18n';
import DashboardCard from '../components/DashboardCard.vue';
import MetricCard from '../components/MetricCard.vue';

interface Props {
    hostelDashboard: HostelDashboard;
}

const props = defineProps<Props>();

const { summary, blocks, genderSplit, queryStats, applicationStats } = props.hostelDashboard;

const occupancySubtext = computed(() =>
    trans('dashboard.hostel_occupancy_rate', { rate: String(summary.occupancyRate) }),
);

const capacitySubtext = computed(() =>
    trans('dashboard.hostel_across_blocks', { count: String(summary.blocks) }),
);

const availableSubtext = computed(() =>
    trans('dashboard.hostel_vacant_rooms', { count: String(summary.vacantRooms) }),
);

const collectionSubtext = computed(() =>
    trans('dashboard.hostel_paid_applications', {
        paid: String(applicationStats.paid + applicationStats.approved),
        total: String(applicationStats.total),
    }),
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
    if (rate <= 70) return 'bg-emerald-100 text-emerald-700';
    if (rate <= 90) return 'bg-amber-100 text-amber-700';

    return 'bg-rose-100 text-rose-700';
};

const blockIconClass = (block: HostelDashboardBlock): string => {
    if (block.maintenanceRooms > 0) {
        return 'bg-amber-50 text-amber-700';
    }

    if (block.type === 'female') {
        return 'bg-pink-50 text-pink-700';
    }

    if (block.type === 'male') {
        return 'bg-blue-50 text-blue-600';
    }

    return 'bg-emerald-50 text-emerald-700';
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
    <div class="mt-4 flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
                :title="$t('dashboard.hostel_total_capacity')"
                :value="summary.totalCapacity"
                :subtext="capacitySubtext"
                trend="neutral"
            >
                <template #icon><Bed class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.hostel_currently_occupied')"
                :value="summary.occupiedBeds"
                :subtext="occupancySubtext"
                :trend="summary.occupancyRate > 90 ? 'warning' : 'neutral'"
            >
                <template #icon><UserCheck class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.hostel_beds_available')"
                :value="summary.availableBeds"
                :subtext="availableSubtext"
                trend="neutral"
            >
                <template #icon><DoorOpen class="h-4 w-4" /></template>
            </MetricCard>
            <MetricCard
                :title="$t('dashboard.hostel_fee_collection')"
                :value="`${applicationStats.paidRate}%`"
                :subtext="collectionSubtext"
                :trend="applicationStats.paidRate < 80 ? 'warning' : 'neutral'"
            >
                <template #icon><Coins class="h-4 w-4" /></template>
            </MetricCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <DashboardCard :title="$t('dashboard.hostel_occupancy_by_block')">
                <Empty v-if="blocks.length === 0" :message="$t('hms.no_hostels_found')" />
                <div v-else class="mt-1 flex flex-col gap-0">
                    <div
                        v-for="block in blocks"
                        :key="block.id"
                        class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-0"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                            :class="blockIconClass(block)"
                        >
                            <AlertTriangle v-if="block.maintenanceRooms > 0" class="h-5 w-5" />
                            <Building v-else class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[13px] font-medium text-gray-900">{{ blockTitle(block) }}</div>
                            <div
                                class="text-xs"
                                :class="block.maintenanceRooms > 0 ? 'text-rose-600' : 'text-gray-500'"
                            >
                                {{ block.subtitle }}
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-sm bg-gray-100">
                                <div
                                    class="h-1.5 rounded-sm"
                                    :class="blockBarClass(block)"
                                    :style="{ width: `${block.occupancyRate}%` }"
                                />
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <div class="text-[13px] font-medium text-gray-900">{{ block.occupied }}/{{ block.capacity }}</div>
                            <div class="mt-0.5 text-xs text-gray-500">
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
                        <div class="w-24 shrink-0 text-xs text-gray-900">{{ row.label }}</div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-sm bg-gray-100">
                            <div class="h-1.5 rounded-sm" :class="row.barClass" :style="{ width: `${row.percent}%` }" />
                        </div>
                        <div class="w-8 text-right text-xs text-gray-500">{{ row.count }}</div>
                    </div>
                </div>
            </DashboardCard>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <DashboardCard :title="$t('dashboard.hostel_maintenance_facilities')">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_open_queries') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ queryStats.open }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_high_priority_queries') }}</span>
                        <span class="text-xs font-medium text-gray-900">
                            <span
                                v-if="queryStats.highPriority > 0"
                                class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700"
                            >
                                {{ queryStats.highPriority }}
                            </span>
                            <span v-else>{{ queryStats.highPriority }}</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_in_progress_queries') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ queryStats.inProgress }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_resolved_this_month') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ queryStats.resolvedThisMonth }}</span>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.hostel_fees_payments')">
                <div class="mt-2 flex flex-col gap-0">
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_fully_paid') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ applicationStats.paid + applicationStats.approved }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_partial_payment') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ applicationStats.partiallyPaid }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_awaiting_payment') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ applicationStats.awaitingPayment }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_pending_applications') }}</span>
                        <span class="text-xs font-medium text-gray-900">{{ applicationStats.pending }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 last:border-0">
                        <span class="text-xs text-gray-500">{{ $t('dashboard.hostel_declined_applications') }}</span>
                        <span class="text-xs font-medium text-gray-900">
                            <span
                                v-if="applicationStats.declined > 0"
                                class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700"
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
