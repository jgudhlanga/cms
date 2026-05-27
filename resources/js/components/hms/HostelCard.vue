<script setup lang="ts">
import {
    Bed,
    Castle,
    CheckCircle,
    Circle,
    DoorClosed,
    Layers,
    Mars,
    Mountain,
    ShieldUser,
    TreePine,
    Users,
    Venus,
} from '@lucide/vue';
import { IconButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Hostel } from '@/types/hms';

interface Props {
    hostel: Hostel;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'edit', hostel: Hostel): void;
    (e: 'delete', hostel: Hostel): void;
}>();

// ── Warden display name ──────────────────────────────────────────────────────
const wardenName = computed<string>(() => {
    if (props.hostel.attributes.wardenName) {
        return props.hostel.attributes.wardenName;
    }
    const staff = props.hostel.attributes.warden;
    if (staff?.relationships?.user) {
        const u = staff.relationships.user.attributes;
        return u.name || '—';
    }
    return '—';
});

// ── Type helpers ─────────────────────────────────────────────────────────────
const isFemale = computed(() => props.hostel.attributes.type === 'female');
const isMale   = computed(() => props.hostel.attributes.type === 'male');

// ── Occupancy ────────────────────────────────────────────────────────────────
const occupied         = computed(() => props.hostel.attributes.occupiedCount ?? 0);
const availableBeds    = computed(() => props.hostel.attributes.capacity - occupied.value);
const occupancyPercent = computed(() =>
    props.hostel.attributes.capacity > 0 ? (occupied.value / props.hostel.attributes.capacity) * 100 : 0,
);
const occupancyBarClass = computed(() => {
    const pct = occupancyPercent.value;
    if (pct <= 70) return 'bg-emerald-500';
    if (pct <= 90) return 'bg-amber-500';
    return 'bg-rose-500';
});

// ── Location abbreviation ────────────────────────────────────────────────────
const locationAbbr = computed(() => {
    const loc = props.hostel.attributes.location ?? '';
    if (loc.toLowerCase().includes('north')) return 'NW';
    if (loc.toLowerCase().includes('south')) return 'SW';
    return loc.slice(0, 2).toUpperCase() || '—';
});
const locationIcon = computed(() =>
    (props.hostel.attributes.location ?? '').toLowerCase().includes('north') ? Mountain : TreePine,
);
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-xl">
        <!-- Gender / type accent strip -->
        <div
            class="h-0.5"
            :class="
                isFemale
                    ? 'bg-linear-to-r from-rose-300 to-pink-500'
                    : isMale
                      ? 'bg-linear-to-r from-sky-400 to-blue-600'
                      : 'bg-linear-to-r from-violet-400 to-indigo-500'
            "
        />

        <div class="p-5 sm:p-6">
            <!-- ── Header ─────────────────────────────────────────────────── -->
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                    <h2 class="text-sm font-semibold tracking-tight text-foreground">{{ hostel.attributes.name }}</h2>

                    <!-- Status badge -->
                    <span
                        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                        :class="
                            hostel.attributes.status === 'active'
                                ? 'border-green-200 bg-green-50 text-green-700'
                                : 'border-border bg-muted text-muted-foreground'
                        "
                    >
                        <Circle
                            class="h-1.5 w-1.5 fill-current"
                            :class="hostel.attributes.status === 'active' ? 'text-green-500' : 'text-muted-foreground'"
                        />
                        {{ hostel.attributes.status === 'active' ? $t('hms.status_active') : $t('hms.status_inactive') }}
                    </span>
                </div>

                <!-- Type + location tags -->
                <div v-if="hostel.attributes.type" class="flex items-center gap-1.5">
                    <span
                        class="rounded-full border px-2 py-1 text-xs font-medium"
                        :class="
                            isFemale
                                ? 'border-pink-200 bg-pink-100 text-pink-700'
                                : isMale
                                  ? 'border-blue-200 bg-blue-100 text-blue-700'
                                  : 'border-violet-200 bg-violet-100 text-violet-700'
                        "
                    >
                        <component :is="isFemale ? Venus : Mars" class="mr-1 inline h-2.5 w-2.5" />
                        {{ $t(`hms.type_${hostel.attributes.type}`) }}
                    </span>

                    <span
                        v-if="hostel.attributes.location"
                        class="text-muted-foreground flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs"
                    >
                        <component :is="locationIcon" class="h-2.5 w-2.5" />
                        {{ locationAbbr }}
                    </span>
                </div>
            </div>

            <!-- ── Stats grid ─────────────────────────────────────────────── -->
            <div class="mb-4 grid grid-cols-3 gap-2 rounded-xl bg-muted/60 p-2.5 text-center">
                <div class="flex flex-col items-center">
                    <Layers class="text-muted-foreground h-4 w-4" />
                    <span class="text-muted-foreground mt-1 text-xs">{{ $t('hms.floors') }}</span>
                    <span class="text-foreground font-semibold">{{ hostel.attributes.floorCount }}</span>
                </div>
                <div class="flex flex-col items-center">
                    <DoorClosed class="text-muted-foreground h-4 w-4" />
                    <span class="text-muted-foreground mt-1 text-xs">{{ $t('hms.rooms') }}</span>
                    <span class="text-foreground font-semibold">{{ hostel.attributes.roomsCount }}</span>
                </div>
                <div class="flex flex-col items-center">
                    <Users class="text-muted-foreground h-4 w-4" />
                    <span class="text-muted-foreground mt-1 text-xs">{{ $tChoice('hms.capacity', 1) }}</span>
                    <span class="text-foreground font-semibold">{{ hostel.attributes.capacity }}</span>
                </div>
            </div>

            <!-- ── Occupancy bar ──────────────────────────────────────────── -->
            <div class="mb-4">
                <div class="mb-1.5 flex items-center justify-between text-xs">
                    <span class="text-muted-foreground font-medium">
                        <Bed class="mr-1 inline h-3 w-3 text-indigo-400" />{{ $t('hms.bed_occupancy') }}
                    </span>
                    <span class="text-muted-foreground">{{ occupied }} / {{ hostel.attributes.capacity }} {{ $t('hms.filled') }}</span>
                </div>
                <div class="bg-muted h-2 w-full overflow-hidden rounded-full">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="occupancyBarClass"
                        :style="{ width: `${occupancyPercent}%` }"
                    />
                </div>
                <div class="mt-1.5 flex justify-between text-xs">
                    <span class="text-emerald-600">
                        <CheckCircle class="mr-1 inline h-2.5 w-2.5" />{{ $t('hms.available') }}: {{ availableBeds }} {{ $t('hms.beds') }}
                    </span>
                    <span class="text-muted-foreground">{{ $t('hms.occupied_pct', { pct: String(Math.round(occupancyPercent)) }) }}</span>
                </div>
            </div>

            <!-- ── Warden & actions ───────────────────────────────────────── -->
            <div class="mt-1 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3">
                <div class="flex items-center gap-2 text-sm">
                    <ShieldUser class="h-4 w-4 text-indigo-400" />
                    <span class="text-foreground font-medium">{{ $tChoice('hms.warden', 1) }}:</span>
                    <span class="text-muted-foreground">{{ wardenName }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- View -->
                    <IconButton
                        :id="`hostel-view-${hostel.id}`"
                        :icon="IconName.eye"
                        :variant="ColorVariant.primary_outline"
                        @click="router.get(route('hostels.show', String(hostel.id)))"
                    />

                    <!-- Edit -->
                    <IconButton
                        :id="`hostel-edit-${hostel.id}`"
                        :icon="IconName.edit"
                        :variant="ColorVariant.success_outline"
                        @click="emit('edit', hostel)"
                    />
                </div>
            </div>

            <!-- Ref footer -->
            <div class="text-muted-foreground mt-3 flex items-center justify-end gap-1 text-right text-[11px]">
                <Castle class="h-3 w-3" />
                <span>{{ hostel.attributes.roomsCount }} {{ $t('hms.rooms') }} • {{ hostel.attributes.floorCount }} {{ $t('hms.floors') }}</span>
            </div>
        </div>
    </div>
</template>
