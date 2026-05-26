<script setup lang="ts">
import type { HostelRoomViewModel, HostelShowStatusFilter } from '@/composables/hms/useHostelShow';
import HostelRoomCard from '@/pages/hms/hostels/partials/HostelRoomCard.vue';
import HostelRoomFilters from '@/pages/hms/hostels/partials/HostelRoomFilters.vue';
import { Plus } from '@lucide/vue';

interface Props {
    rooms: HostelRoomViewModel[];
    filteredRooms: HostelRoomViewModel[];
    floorTabs: number[];
    activeFloor: number;
    statusFilter: HostelShowStatusFilter;
    searchQuery: string;
    isLoading?: boolean;
    canAddRoom?: boolean;
}

withDefaults(defineProps<Props>(), {
    isLoading: false,
    canAddRoom: true,
});

const emit = defineEmits<{
    'update:activeFloor': [value: number];
    'update:statusFilter': [value: HostelShowStatusFilter];
    'update:searchQuery': [value: string];
    selectRoom: [room: HostelRoomViewModel];
    addRoom: [];
}>();
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $t('hms.show_room_catalogue') }}</h2>
                <p class="text-sm text-slate-400">
                    {{ $t('hms.show_rooms_shown', { count: filteredRooms.length }) }}
                </p>
            </div>
            <button
                v-if="canAddRoom"
                type="button"
                class="inline-flex items-center gap-2 rounded-[10px] bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                @click="emit('addRoom')"
            >
                <Plus class="h-4 w-4" />
                {{ $t('hms.add_room') }}
            </button>
        </div>

        <HostelRoomFilters
            :total-rooms="rooms.length"
            :floor-tabs="floorTabs"
            :active-floor="activeFloor"
            :status-filter="statusFilter"
            :search-query="searchQuery"
            @update:active-floor="emit('update:activeFloor', $event)"
            @update:status-filter="emit('update:statusFilter', $event)"
            @update:search-query="emit('update:searchQuery', $event)"
        />

        <div v-if="isLoading" class="py-12 text-center text-sm text-slate-400">
            {{ $t('trans.loading') }}…
        </div>
        <div v-else-if="filteredRooms.length === 0" class="py-12 text-center text-sm text-slate-400">
            {{ $t('hms.no_rooms_found') }}
        </div>
        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <HostelRoomCard
                v-for="room in filteredRooms"
                :key="room.id"
                :room="room"
                @select="emit('selectRoom', $event)"
            />
        </div>
    </section>
</template>
