<script setup lang="ts">
import type { HostelAllocation, HostelAllocationRoommate } from '@/types/hms';
import { Bed, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    allocation: HostelAllocation | null;
    roommates: HostelAllocationRoommate[];
}

const props = defineProps<Props>();

const attrs = computed(() => props.allocation?.attributes ?? null);

const roomTypeLabel = computed(() => {
    const type = attrs.value?.roomType;
    if (!type) {
        return '—';
    }

    return type.charAt(0).toUpperCase() + type.slice(1);
});

function roommateInitials(name?: string | null): string {
    if (!name) {
        return '?';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function roommateSubtitle(roommate: HostelAllocationRoommate): string {
    const parts = [roommate.course, roommate.level].filter(Boolean);
    return parts.join(' — ');
}
</script>

<template>
    <div v-if="!allocation" class="rounded-xl border border-dashed border-border bg-muted/20 py-10 text-center">
        <p class="text-sm text-muted-foreground">{{ $t('students.accommodation_no_room_assigned') }}</p>
    </div>

    <div v-else class="flex flex-col gap-4">
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
            <div class="flex items-start gap-3 border-b border-border pb-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                    <Bed class="h-5 w-5" />
                </span>
                <div>
                    <h4 class="text-lg font-semibold text-foreground">
                        {{ $t('students.accommodation_room_title', { room: attrs?.roomName ?? '—' }) }}
                    </h4>
                    <p class="text-sm text-muted-foreground">{{ attrs?.hostelName }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_floor') }}</p>
                    <p class="font-medium text-foreground">{{ attrs?.floorNumber ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_type') }}</p>
                    <p class="font-medium text-foreground">{{ roomTypeLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_occupancy') }}</p>
                    <p class="font-medium text-foreground">{{ attrs?.occupancyLabel ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_room_status') }}</p>
                    <span
                        class="inline-flex rounded-full bg-primary px-2.5 py-0.5 text-xs font-medium capitalize text-primary-foreground"
                    >
                        {{ attrs?.roomStatus ?? attrs?.statusLabel }}
                    </span>
                </div>
            </div>

            <div class="mt-4">
                <p class="mb-2 text-xs text-muted-foreground">{{ $t('students.accommodation_amenities') }}</p>
                <div v-if="attrs?.amenities?.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="amenity in attrs.amenities"
                        :key="amenity"
                        class="rounded-full border border-border bg-muted/40 px-3 py-1 text-xs text-foreground"
                    >
                        {{ amenity }}
                    </span>
                </div>
                <p v-else class="text-sm text-muted-foreground">{{ $t('students.accommodation_no_amenities') }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
            <div class="mb-3 flex items-center gap-2">
                <Users class="h-4 w-4 text-muted-foreground" />
                <h4 class="font-semibold text-foreground">{{ $t('students.accommodation_roommates') }}</h4>
            </div>

            <div v-if="roommates.length === 0" class="text-sm text-muted-foreground">
                {{ $t('students.accommodation_no_roommates') }}
            </div>

            <ul v-else class="flex flex-col gap-2">
                <li
                    v-for="roommate in roommates"
                    :key="roommate.id"
                    class="flex items-center gap-3 rounded-lg bg-muted/30 px-3 py-2"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white dark:bg-emerald-700"
                    >
                        {{ roommateInitials(roommate.name) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium text-foreground">{{ roommate.name }}</p>
                        <p v-if="roommateSubtitle(roommate)" class="truncate text-xs text-muted-foreground">
                            {{ roommateSubtitle(roommate) }}
                        </p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
