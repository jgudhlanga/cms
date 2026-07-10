<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { amenityIconName } from '@/lib/hms/roomSectionDisplay';
import type { HostelAllocation, HostelAllocationAmenity, HostelAllocationRoommate } from '@/types/hms';
import { Bed, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    allocation: HostelAllocation | null;
    roommates: HostelAllocationRoommate[];
}

const props = defineProps<Props>();

const { formatCurrency } = useUtils();

const attrs = computed(() => props.allocation?.attributes ?? null);

const roomTypeLabel = computed(() => {
    const type = attrs.value?.roomType;
    if (!type) {
        return '—';
    }

    return type.charAt(0).toUpperCase() + type.slice(1);
});

const hasAmenityMarketValues = computed(
    () => (attrs.value?.amenities ?? []).some((amenity) => amenity.marketValue != null),
);

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

function amenityMarketValueLabel(amenity: HostelAllocationAmenity): string | null {
    if (amenity.marketValue == null) {
        return null;
    }

    return `USD ${formatCurrency(String(amenity.marketValue))}`;
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
                <div v-if="attrs?.sectionName">
                    <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_section') }}</p>
                    <p class="font-medium text-foreground">{{ attrs.sectionName }}</p>
                </div>
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
                        class="inline-flex rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-medium capitalize text-white dark:bg-emerald-700"
                    >
                        {{ attrs?.roomStatus ?? attrs?.statusLabel }}
                    </span>
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-2">
                    <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_amenities') }}</p>
                    <p
                        v-if="hasAmenityMarketValues"
                        class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
                    >
                        {{ $t('students.accommodation_amenity_market_value_hint') }}
                    </p>
                </div>
                <div
                    v-if="attrs?.amenities?.length"
                    class="grid w-full grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-[repeat(auto-fill,minmax(7rem,1fr))]"
                >
                    <div
                        v-for="amenity in attrs.amenities"
                        :key="amenity.id"
                        class="flex min-w-0 w-full items-center rounded-xl border border-primary/15 bg-primary/5 px-2 py-1"
                    >
                        <span class="inline-flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs font-medium text-foreground">
                            <BaseIcon
                                :name="amenityIconName(amenity.slug ?? amenity.name)"
                                class="h-3.5 w-3.5 shrink-0 text-primary"
                            />
                            <span class="truncate">{{ amenity.name }}</span>
                            <span
                                v-if="amenityMarketValueLabel(amenity)"
                                class="text-[10px] font-normal leading-none text-muted-foreground"
                            >
                                {{ amenityMarketValueLabel(amenity) }}
                            </span>
                        </span>
                    </div>
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
