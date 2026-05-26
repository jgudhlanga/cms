<script setup lang="ts">
import HostelStatCard from '@/pages/hms/hostels/partials/HostelStatCard.vue';
import type { HostelShowStats } from '@/composables/hms/useHostelShow';
import { CheckCircle, DoorClosed, LayoutGrid, PieChart } from '@lucide/vue';

interface Props {
    stats: HostelShowStats;
}

defineProps<Props>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <HostelStatCard
            :label="$t('hms.show_stat_total_rooms')"
            :value="stats.totalRooms"
            :icon="LayoutGrid"
            icon-class="bg-indigo-50 text-indigo-600"
            value-class="text-indigo-600"
        />
        <HostelStatCard
            :label="$t('hms.show_stat_occupied_rooms')"
            :value="stats.occupiedRooms"
            :icon="DoorClosed"
            icon-class="bg-pink-50 text-pink-600"
            value-class="text-pink-600"
        />
        <HostelStatCard
            :label="$t('hms.show_stat_available_rooms')"
            :value="stats.availableRooms"
            :icon="CheckCircle"
            icon-class="bg-emerald-50 text-emerald-600"
            value-class="text-emerald-600"
        />
        <HostelStatCard
            :label="$t('hms.show_stat_occupancy_rate')"
            :value="`${stats.occupancyRate}%`"
            :icon="PieChart"
            icon-class="bg-amber-50 text-amber-600"
            value-class="text-amber-600"
        />
    </div>
</template>
