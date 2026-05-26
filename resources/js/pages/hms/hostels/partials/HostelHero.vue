<script setup lang="ts">
import type { HostelShowSnapshot, HostelShowStats } from '@/composables/hms/useHostelShow';
import { Mars, MapPin, Pencil, Venus } from '@lucide/vue';
import { computed } from 'vue';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { icons } from '@/lib/icons';
import {ButtonSize} from '@/enums/buttons';

interface Props {
    hostel: HostelShowSnapshot;
    stats: HostelShowStats;
    occupiedBeds: number;
    availableBeds: number;
    canEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: true,
});

const emit = defineEmits<{
    edit: [];
}>();

const isActive = computed(() => props.hostel.status === 'active');
const isFemale = computed(() => props.hostel.type === 'female');
const isMale = computed(() => props.hostel.type === 'male');

const typeLabel = computed(() => {
    if (!props.hostel.type) {
        return null;
    }

    return `hms.type_${props.hostel.type}`;
});

const genderChipClass = computed(() => {
    if (isFemale.value) {
        return 'border-pink-200 bg-pink-50 text-pink-700';
    }

    if (isMale.value) {
        return 'border-blue-200 bg-blue-50 text-blue-700';
    }

    return 'border-violet-200 bg-violet-50 text-violet-700';
});

const metricItems = computed(() => [
    { labelKey: 'hms.floors', value: props.hostel.floor_count, valueClass: 'text-slate-800' },
    { labelKey: 'hms.show_stat_total_rooms', value: props.stats.totalRooms, valueClass: 'text-indigo-600' },
    { labelKey: 'hms.show_stat_occupied_rooms', value: props.stats.occupiedRooms, valueClass: 'text-pink-600' },
    { labelKey: 'hms.show_stat_available_rooms', value: props.stats.availableRooms, valueClass: 'text-emerald-600' },
    { labelKey: 'hms.show_stat_occupancy_rate', value: `${props.stats.occupancyRate}%`, valueClass: 'text-amber-600' },
    { labelKey: 'hms.capacity', value: props.hostel.capacity, valueClass: 'text-slate-800', choice: 1 },
    { labelKey: 'hms.show_occupied_beds', value: props.occupiedBeds, valueClass: 'text-emerald-600' },
    { labelKey: 'hms.show_available_beds', value: props.availableBeds, valueClass: 'text-orange-500' },
]);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                <h1 class="truncate font-serif text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    {{ hostel.name }}
                </h1>
                <span
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                    :class="
                        isActive
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            : 'border-slate-200 bg-slate-100 text-slate-600'
                    "
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full"
                        :class="isActive ? 'bg-emerald-500' : 'bg-slate-400'"
                    />
                    {{ isActive ? $t('hms.status_active') : $t('hms.status_inactive') }}
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span
                    v-if="typeLabel"
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                    :class="genderChipClass"
                >
                    <Venus v-if="isFemale" class="h-3.5 w-3.5" />
                    <Mars v-else-if="isMale" class="h-3.5 w-3.5" />
                    {{ $t(typeLabel) }}
                </span>
                <!-- Edit -->
                <BaseButton
                    v-if="canEdit"
                    :id="`hostel-edit-${hostel.id}`"
                    :size="ButtonSize.sm"
                    classes="rounded-full"
                    :variant="ColorVariant.shade"
                    @click="emit('edit', hostel)"
                >
                    <component :is="icons[IconName.edit]" />
                    {{ $t('trans.edit') }}
                </BaseButton>
            </div>
        </div>

        <p v-if="hostel.location" class="mt-1.5 flex items-center gap-1 text-xs text-slate-500">
            <MapPin class="h-3.5 w-3.5 shrink-0" />
            {{ hostel.location }}
        </p>

        <div
            class="mt-2.5 flex flex-wrap items-center divide-x divide-slate-200 border-t border-slate-100 pt-2.5"
        >
            <div
                v-for="item in metricItems"
                :key="item.labelKey"
                class="flex items-baseline gap-1 px-3 py-0.5 first:pl-0"
            >
                <span class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                    <template v-if="item.choice !== undefined">{{ $tChoice(item.labelKey, item.choice) }}</template>
                    <template v-else>{{ $t(item.labelKey) }}</template>
                </span>
                <span class="text-sm font-semibold leading-none" :class="item.valueClass">
                    {{ item.value }}
                </span>
            </div>
        </div>
    </div>
</template>
