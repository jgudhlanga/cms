<script setup lang="ts">
import type { HostelRoomViewModel } from '@/composables/hms/useHostelShow';
import { descriptionFeatureChips, formatFloorLabel } from '@/lib/hms/hostelRoomDisplay';
import { trans } from 'laravel-vue-i18n';
import { Bed, X } from '@lucide/vue';
import { computed } from 'vue';

interface Props {
    room: HostelRoomViewModel | null;
    hostelName: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const features = computed(() => descriptionFeatureChips(props.room?.description));

const roomTypeLabel = computed(() => (props.room ? `hms.room_type_${props.room.roomType}` : ''));

const availableBeds = computed(() => {
    if (!props.room) {
        return 0;
    }

    return Math.max(0, props.room.max - props.room.current);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="room"
            class="fixed inset-0 z-200 flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-[20px] border border-border bg-card shadow-2xl">
                <div class="bg-card sticky top-0 z-10 flex items-center justify-between border-b border-border px-6 py-4">
                    <div>
                        <div class="text-foreground font-serif text-2xl font-black">{{ room.name }}</div>
                        <div class="text-muted-foreground text-xs">
                            {{ hostelName }}
                            ·
                            {{ formatFloorLabel(room.floorNumber, trans) }}
                            ·
                            {{ $t(roomTypeLabel) }}
                        </div>
                    </div>
                    <button
                        type="button"
                        class="text-muted-foreground hover:bg-muted hover:text-foreground flex h-8 w-8 items-center justify-center rounded-lg border border-border"
                        @click="emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-muted/60 rounded-xl p-3 text-center">
                            <div class="font-serif text-2xl font-black text-indigo-600">{{ room.max }}</div>
                            <div class="text-muted-foreground text-[11px]">{{ $t('hms.show_modal_total_beds') }}</div>
                        </div>
                        <div class="bg-muted/60 rounded-xl p-3 text-center">
                            <div class="font-serif text-2xl font-black text-pink-600">{{ room.current }}</div>
                            <div class="text-muted-foreground text-[11px]">{{ $t('hms.show_modal_occupied') }}</div>
                        </div>
                        <div class="bg-muted/60 rounded-xl p-3 text-center">
                            <div class="font-serif text-2xl font-black text-emerald-600">{{ availableBeds }}</div>
                            <div class="text-muted-foreground text-[11px]">{{ $t('hms.available') }}</div>
                        </div>
                    </div>

                    <div v-if="features.length">
                        <div class="text-foreground mb-2 text-sm font-bold">{{ $t('hms.show_modal_features') }}</div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="feature in features"
                                :key="feature"
                                class="text-muted-foreground rounded-lg bg-muted px-3 py-1 text-xs"
                            >
                                {{ feature }}
                            </span>
                        </div>
                    </div>
                    <p v-else-if="room.description" class="text-muted-foreground text-sm">{{ room.description }}</p>

                    <div>
                        <div class="text-foreground mb-2 text-sm font-bold">{{ $t('hms.show_modal_assigned_students') }}</div>
                        <div v-if="room.students.length" class="flex flex-col gap-2">
                            <div
                                v-for="student in room.students"
                                :key="student.id"
                                class="bg-muted/60 flex items-center gap-3 rounded-xl p-3"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                    :style="{ background: student.color }"
                                >
                                    {{ student.initials }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-foreground font-semibold">{{ student.name }}</div>
                                    <div class="text-muted-foreground text-xs">
                                        {{ student.studentNumber }} · {{ student.course }}
                                    </div>
                                    <div v-if="student.level" class="text-muted-foreground text-[11px]">{{ student.level }}</div>
                                </div>
                                <span
                                    v-if="student.sectionName"
                                    class="text-muted-foreground shrink-0 rounded-full border border-border bg-muted/40 px-2 py-0.5 text-[10px] font-bold"
                                    :title="$t('hms.room_section_name', { name: student.sectionName })"
                                >
                                    {{ student.sectionName }}
                                </span>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                    {{ $t('hms.allocation_status_active') }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-muted-foreground py-8 text-center text-sm">
                            <Bed class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            {{ $t('hms.show_room_no_students') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
