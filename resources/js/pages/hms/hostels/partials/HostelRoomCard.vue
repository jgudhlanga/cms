<script setup lang="ts">
import type { HostelRoomViewModel } from '@/composables/hms/useHostelShow';
import { formatFloorLabel } from '@/lib/hms/hostelRoomDisplay';
import { Bed } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    room: HostelRoomViewModel;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    select: [room: HostelRoomViewModel];
}>();

const statusLabelKey = computed(() => {
    switch (props.room.availability) {
        case 'available':
            return 'hms.show_room_status_available';
        case 'partial':
            return 'hms.show_room_status_partial';
        case 'full':
            return 'hms.show_room_status_full';
        case 'maintenance':
            return 'hms.room_status_maintenance';
        default:
            return 'hms.show_room_status_available';
    }
});

const pillClass = computed(() => {
    switch (props.room.availability) {
        case 'available':
            return 'bg-emerald-100 text-emerald-700';
        case 'partial':
            return 'bg-amber-100 text-amber-700';
        case 'full':
            return 'bg-red-100 text-red-700';
        case 'maintenance':
            return 'bg-slate-100 text-slate-600';
        default:
            return 'bg-emerald-100 text-emerald-700';
    }
});

const cardBorderClass = computed(() => {
    if (props.room.availability === 'full') {
        return 'border-red-200';
    }

    if (props.room.availability === 'partial') {
        return 'border-pink-200';
    }

    return 'border-slate-200';
});

const roomTypeLabel = computed(() => `hms.room_type_${props.room.roomType}`);
</script>

<template>
    <button
        type="button"
        class="w-full overflow-hidden rounded-2xl border-[1.5px] bg-white text-left transition hover:-translate-y-0.5 hover:shadow-lg"
        :class="cardBorderClass"
        @click="emit('select', room)"
    >
        <div class="flex items-start justify-between border-b border-slate-200 px-4 py-3">
            <div>
                <div class="font-serif text-xl font-black text-slate-900">{{ room.name }}</div>
                <div class="mt-0.5 text-xs text-slate-400">
                    {{ formatFloorLabel(room.floorNumber, trans) }}
                    ·
                    {{ $t(roomTypeLabel) }}
                </div>
            </div>
            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" :class="pillClass">
                {{ $t(statusLabelKey) }}
            </span>
        </div>

        <div class="px-4 py-3">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex gap-1">
                    <div
                        v-for="slot in Math.max(room.max, 1)"
                        :key="slot"
                        class="flex h-[22px] w-7 items-center justify-center rounded border text-[10px]"
                        :class="
                            slot <= room.current
                                ? 'border-pink-300 bg-pink-100 text-pink-600'
                                : 'border-slate-200 bg-slate-50 text-transparent'
                        "
                    >
                        <Bed v-if="slot <= room.current" class="h-3 w-3" />
                    </div>
                </div>
                <span class="text-xs text-slate-400">{{ room.current }}/{{ room.max }} {{ $t('hms.beds') }}</span>
            </div>

            <div v-if="room.students.length" class="flex flex-col gap-1.5">
                <div
                    v-for="student in room.students"
                    :key="student.id"
                    class="flex items-center gap-2 rounded-lg bg-slate-50 px-2 py-1.5"
                >
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                        :style="{ background: student.color }"
                    >
                        {{ student.initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-semibold text-slate-800">{{ student.name }}</div>
                        <div v-if="student.level" class="truncate text-[10px] text-slate-400">
                            {{ student.level }}
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="px-1 text-xs italic text-slate-400">
                {{ $t('hms.show_room_no_students') }}
            </p>
        </div>
    </button>
</template>
