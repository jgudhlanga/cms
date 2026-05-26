<script setup lang="ts">
import { HOSTEL_SHOW_ALL_FLOORS, type HostelShowStatusFilter } from '@/composables/hms/useHostelShow';
import { formatFloorLabel } from '@/lib/hms/hostelRoomDisplay';
import { Search } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';

interface Props {
    totalRooms: number;
    floorTabs: number[];
    activeFloor: number;
    statusFilter: HostelShowStatusFilter;
    searchQuery: string;
}

defineProps<Props>();

const emit = defineEmits<{
    'update:activeFloor': [value: number];
    'update:statusFilter': [value: HostelShowStatusFilter];
    'update:searchQuery': [value: string];
}>();
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-lg border-[1.5px] px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeFloor === HOSTEL_SHOW_ALL_FLOORS
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                "
                @click="emit('update:activeFloor', HOSTEL_SHOW_ALL_FLOORS)"
            >
                {{ $t('hms.show_all_floors') }}
            </button>
            <button
                v-for="floor in floorTabs"
                :key="floor"
                type="button"
                class="rounded-lg border-[1.5px] px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeFloor === floor
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                "
                @click="emit('update:activeFloor', floor)"
            >
                {{ formatFloorLabel(floor, trans) }}
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-full border-[1.5px] px-4 py-2 text-sm font-semibold transition"
                :class="
                    statusFilter === 'all'
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                "
                @click="emit('update:statusFilter', 'all')"
            >
                {{ $t('hms.show_filter_all', { count: totalRooms }) }}
            </button>
            <button
                type="button"
                class="rounded-full border-[1.5px] px-4 py-2 text-sm font-semibold transition"
                :class="
                    statusFilter === 'available'
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                "
                @click="emit('update:statusFilter', 'available')"
            >
                {{ $t('hms.show_filter_available') }}
            </button>
            <button
                type="button"
                class="rounded-full border-[1.5px] px-4 py-2 text-sm font-semibold transition"
                :class="
                    statusFilter === 'partial'
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                "
                @click="emit('update:statusFilter', 'partial')"
            >
                {{ $t('hms.show_filter_partial') }}
            </button>
            <button
                type="button"
                class="rounded-full border-[1.5px] px-4 py-2 text-sm font-semibold transition"
                :class="
                    statusFilter === 'full'
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                "
                @click="emit('update:statusFilter', 'full')"
            >
                {{ $t('hms.show_filter_full') }}
            </button>

            <label
                class="ml-auto flex min-w-[200px] items-center gap-2 rounded-[10px] border-[1.5px] border-slate-200 bg-white px-3 py-2 text-sm text-slate-400"
            >
                <Search class="h-4 w-4 shrink-0" />
                <input
                    :value="searchQuery"
                    type="search"
                    class="w-full min-w-0 border-0 bg-transparent text-slate-800 outline-none placeholder:text-slate-400"
                    :placeholder="$t('hms.show_search_room_placeholder')"
                    @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
                />
            </label>
        </div>
    </div>
</template>
