<script setup lang="ts">
import { Bed, DoorOpen, Users, LayoutGrid } from '@lucide/vue';
import HostelStatsBadge from '@/components/hms/HostelStatsBadge.vue';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelRoomStats } from '@/types/hms';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const { fetchRoomStats } = useHms();
const { roomRefreshKey } = storeToRefs(useHmsStore());

const stats = ref<HostelRoomStats>({
    totalRooms: 0,
    totalCapacity: 0,
    totalMaxOccupancy: 0,
    vacantCount: 0,
});

const loadStats = async () => {
    const res = await fetchRoomStats();
    if (res) stats.value = res;
};

onMounted(() => loadStats());
watch(roomRefreshKey, () => loadStats());
</script>

<template>
    <div class="my-3 flex flex-wrap items-center gap-3">
        <HostelStatsBadge
            :label="$tChoice('hms.room', 2)"
            :value="String(stats.totalRooms)"
            :icon="DoorOpen"
            icon-class="text-indigo-500"
            value-class="text-indigo-700"
        />
        <HostelStatsBadge
            :label="$t('hms.total_rooms_capacity')"
            :value="String(stats.totalCapacity)"
            :icon="Users"
            icon-class="text-emerald-600"
            value-class="text-emerald-700"
        />
        <HostelStatsBadge
            :label="$t('hms.max_occupancy')"
            :value="String(stats.totalMaxOccupancy)"
            :icon="Bed"
            icon-class="text-amber-600"
            value-class="text-amber-700"
        />
        <HostelStatsBadge
            :label="$t('hms.room_status_vacant')"
            :value="String(stats.vacantCount)" 
            :icon="LayoutGrid"
            icon-class="text-sky-500"
            value-class="text-sky-700"
        />
    </div>
</template>
