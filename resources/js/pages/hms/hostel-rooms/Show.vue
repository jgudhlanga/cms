<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import CreateEditRoom from '@/pages/hms/components/forms/CreateEditRoom.vue';
import RoomSectionCard from '@/pages/hms/hostel-rooms/partials/RoomSectionCard.vue';
import { openModal, errorAlert, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { amenityIconName } from '@/lib/hms/roomSectionDisplay';
import { hasAbility } from '@/lib/permissions';
import { resolveHostelRoomBackUrl } from '@/lib/hms/hostelRoomNavigation';
import { icons } from '@/lib/icons';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelRoom } from '@/types/hms';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';

type RoomAmenity = {
    id: number | string;
    name: string;
    slug: string;
};

type RoomStudent = {
    id: number | string;
    student_number?: string | null;
    user?: {
        full_name?: string | null;
    } | null;
    course?: string | null;
};

type RoomAllocation = {
    id: number | string;
    hostel_room_section_id?: number | string | null;
    student?: RoomStudent | null;
};

type RoomSection = {
    id: number | string;
    name: string;
    amenities: RoomAmenity[];
};

type RoomHostel = {
    id: number | string;
    name: string;
};

type RoomRecord = {
    id: number | string;
    hostel_id: number | string;
    name: string;
    room_type: 'single' | 'double' | 'triple' | 'suite';
    capacity: number;
    status: 'vacant' | 'occupied' | 'maintenance';
    max_occupancy: number;
    floor_number?: number | null;
    description?: string | null;
    amenities: RoomAmenity[];
    sections: RoomSection[];
    allocations: RoomAllocation[];
    hostel: RoomHostel;
};

interface Props {
    room: RoomRecord;
    amenities: RoomAmenity[];
}

const props = defineProps<Props>();
const page = usePage();
const { roomRefreshKey } = storeToRefs(useHmsStore());

const selectedAmenities = ref<Record<string, number[]>>({});
const savingSectionId = ref<string | null>(null);

const backUrl = computed(() => {
    const origin = typeof window !== 'undefined' ? window.location.origin : 'https://localhost';
    const current = new URL(String(page.url), origin);

    return resolveHostelRoomBackUrl(current.searchParams.get('return'), props.room.hostel.id, origin);
});

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => {
    const crumbs: BreadcrumbItemInterface[] = [
        { transChoiceKey: 'hms.title', href: route('hostels.index') },
    ];

    const hostelShowPath = route('hostels.show', String(props.room.hostel.id));
    const hostelsIndexPath = route('hostels.index');

    if (backUrl.value === hostelsIndexPath || backUrl.value.startsWith(`${hostelsIndexPath}?`)) {
        // Came from the hostels index; keep HMS as the parent crumb.
    } else {
        crumbs.push({
            title: props.room.hostel.name,
            href: backUrl.value.startsWith(hostelShowPath) ? backUrl.value : hostelShowPath,
        });
    }

    crumbs.push({ title: props.room.name });

    return crumbs;
});

const roomTypeLabel = computed(() => `hms.room_type_${props.room.room_type}`);
const roomStatusLabel = computed(() =>
    props.room.status === 'vacant' ? 'hms.room_status_vacant_badge' : `hms.room_status_${props.room.status}`,
);
const canEditRoom = computed(() => hasAbility('update:hostel-rooms'));

const assignedStudentsBySection = computed(() => {
    const studentsBySectionId = props.room.allocations.reduce<
        Record<string, NonNullable<RoomAllocation['student']>>
    >((carry, allocation) => {
        if (! allocation.hostel_room_section_id || ! allocation.student) {
            return carry;
        }

        carry[String(allocation.hostel_room_section_id)] = allocation.student;

        return carry;
    }, {});

    return props.room.sections.reduce<Record<string, { id: number | string; name: string; studentNumber: string; course: string } | null>>(
        (carry, section) => {
            const student = studentsBySectionId[String(section.id)] ?? null;
            carry[String(section.id)] = student
                ? {
                    id: student.id,
                    name: student.user?.full_name?.trim() || '---',
                    studentNumber: student.student_number?.trim() || '---',
                    course: student.course?.trim() || '',
                }
                : null;

            return carry;
        },
        {},
    );
});

const amenitiesById = computed(() =>
    props.amenities.reduce<Record<number, RoomAmenity>>((carry, amenity) => {
        carry[Number(amenity.id)] = amenity;

        return carry;
    }, {}),
);

const linkedAmenitiesBySection = computed(() =>
    props.room.sections.reduce<Record<string, RoomAmenity[]>>((carry, section) => {
        const selectedIds = selectedAmenities.value[String(section.id)]
            ?? section.amenities.map((amenity) => Number(amenity.id));

        carry[String(section.id)] = selectedIds
            .map((id) => amenitiesById.value[id])
            .filter((amenity): amenity is RoomAmenity => Boolean(amenity));

        return carry;
    }, {}),
);

const availableAmenitiesBySection = computed(() =>
    props.room.sections.reduce<Record<string, RoomAmenity[]>>((carry, section) => {
        const selectedIds = new Set(
            selectedAmenities.value[String(section.id)]
                ?? section.amenities.map((amenity) => Number(amenity.id)),
        );
        carry[String(section.id)] = props.amenities.filter((amenity) => !selectedIds.has(Number(amenity.id)));

        return carry;
    }, {}),
);

const occupiedSections = computed(() =>
    Object.values(assignedStudentsBySection.value).filter((student) => Boolean(student)).length,
);

const availableSections = computed(() => Math.max(0, props.room.sections.length - occupiedSections.value));

const totalSectionAmenities = computed(() =>
    props.room.sections.reduce((total, section) => total + section.amenities.length, 0),
);

const totalAmenities = computed(() => props.room.amenities.length + totalSectionAmenities.value);

const metricItems = computed(() => [
    { labelKey: 'hms.max_occupancy', value: props.room.max_occupancy, valueClass: 'text-foreground' },
    { labelKey: 'hms.show_modal_occupied', value: props.room.allocations.length, valueClass: 'text-pink-600' },
    { labelKey: 'hms.show_stat_sections', value: props.room.sections.length, valueClass: 'text-violet-600' },
    { labelKey: 'hms.show_stat_occupied_sections', value: occupiedSections.value, valueClass: 'text-sky-600' },
    { labelKey: 'hms.show_stat_available_sections', value: availableSections.value, valueClass: 'text-cyan-600' },
    { labelKey: 'hms.amenity', value: totalAmenities.value, valueClass: 'text-primary', choice: totalAmenities.value },
]);

const sectionGridClass = computed(() => {
    if (props.room.room_type === 'double') {
        return 'grid grid-cols-1 gap-4 xl:grid-cols-2';
    }

    if (props.room.room_type === 'triple') {
        return 'grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3';
    }

    return 'grid grid-cols-1 gap-4 lg:grid-cols-2';
});

const roomAsModalPayload = computed<HostelRoom>(() => ({
    type: 'hostel-rooms',
    id: props.room.id,
    attributes: {
        hostelId: props.room.hostel_id,
        hostelName: props.room.hostel.name,
        name: props.room.name,
        roomType: props.room.room_type,
        capacity: props.room.capacity,
        occupancy: `${props.room.allocations.length}/${props.room.max_occupancy}`,
        status: props.room.status,
        maxOccupancy: props.room.max_occupancy,
        floorNumber: props.room.floor_number ?? null,
        description: props.room.description ?? null,
    },
}));

const initializeSelections = () => {
    selectedAmenities.value = props.room.sections.reduce<Record<string, number[]>>((carry, section) => {
        carry[String(section.id)] = section.amenities.map((amenity) => Number(amenity.id));

        return carry;
    }, {});
};

initializeSelections();

watch(
    () => props.room.sections,
    () => {
        initializeSelections();
    },
    { deep: true },
);

watch(roomRefreshKey, () => {
    router.reload({ only: ['room', 'amenities'] });
});

const syncSectionAmenities = (
    sectionId: number | string,
    amenityIds: number[],
    successKey: string,
    failureKey: string,
) => {
    savingSectionId.value = String(sectionId);
    selectedAmenities.value = {
        ...selectedAmenities.value,
        [String(sectionId)]: amenityIds,
    };

    router.put(
        route('hostel-rooms.sections.amenities.sync', {
            hostelRoom: String(props.room.id),
            hostelRoomSection: String(sectionId),
        }),
        {
            amenity_ids: amenityIds,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans(successKey));
                router.reload({ only: ['room', 'amenities'] });
            },
            onError: () => {
                initializeSelections();
                errorAlert(trans(failureKey));
            },
            onFinish: () => {
                savingSectionId.value = null;
            },
        },
    );
};

const linkAmenity = (sectionId: number | string, amenityId: number) => {
    const current = selectedAmenities.value[String(sectionId)] ?? [];
    if (current.includes(amenityId)) {
        return;
    }

    syncSectionAmenities(
        sectionId,
        [...current, amenityId],
        'hms.section_amenity_linked',
        'hms.section_amenity_link_failed',
    );
};

const unlinkAmenity = (sectionId: number | string, amenityId: number) => {
    const current = selectedAmenities.value[String(sectionId)] ?? [];
    syncSectionAmenities(
        sectionId,
        current.filter((id) => id !== amenityId),
        'hms.section_amenity_unlinked',
        'hms.section_amenity_unlink_failed',
    );
};

const openEditRoom = () => {
    openModal({ name: APP_MODULE_KEYS.hostel_rooms, edit: roomAsModalPayload.value });
};
</script>

<template>
    <Head :title="room.name" />

    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="backUrl"
        :hasBackNavigationLeading="true"
    >
        <template #backNavigationLeading>
            <HeadingSmall :title="$t('hms.room_view')" />
        </template>

        <div class="space-y-5">
            <div class="rounded-2xl border border-border bg-card px-4 py-3 sm:px-5">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <BaseIcon :name="IconName.room" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="truncate font-serif text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                                    {{ room.name }}
                                </h1>
                                <span
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    {{ $t(roomStatusLabel) }}
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <BaseIcon :name="IconName.hostel" class="h-3.5 w-3.5" />
                                    {{ room.hostel.name }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <BaseIcon :name="IconName.users" class="h-3.5 w-3.5" />
                                    {{ $t(roomTypeLabel) }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <BaseIcon :name="IconName.house" class="h-3.5 w-3.5" />
                                    {{ $t('hms.floor_label', { floor: room.floor_number ?? 0 }) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <BaseButton
                        v-if="canEditRoom"
                        :id="`room-edit-${room.id}`"
                        :size="ButtonSize.sm"
                        classes="rounded-full"
                        :variant="ColorVariant.shade"
                        @click="openEditRoom"
                    >
                        <component :is="icons[IconName.edit]" />
                        {{ $t('trans.edit') }}
                    </BaseButton>
                </div>

                <div
                    class="mt-2.5 flex flex-wrap items-center divide-x divide-border border-t border-border pt-2.5"
                >
                    <div
                        v-for="item in metricItems"
                        :key="item.labelKey"
                        class="flex items-baseline gap-1 px-3 py-0.5 first:pl-0"
                    >
                        <span class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                            <template v-if="item.choice !== undefined">{{ $tChoice(item.labelKey, item.choice) }}</template>
                            <template v-else>{{ $t(item.labelKey) }}</template>
                        </span>
                        <span class="text-sm font-semibold leading-none" :class="item.valueClass">
                            {{ item.value }}
                        </span>
                    </div>
                </div>

                <div v-if="room.amenities.length" class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="amenity in room.amenities"
                        :key="amenity.id"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-muted/30 px-2.5 py-1 text-xs font-medium text-foreground"
                    >
                        <BaseIcon :name="amenityIconName(amenity.slug || amenity.name)" class="h-3.5 w-3.5 text-muted-foreground" />
                        {{ amenity.name }}
                    </span>
                </div>
            </div>

            <div :class="sectionGridClass">
                <RoomSectionCard
                    v-for="section in room.sections"
                    :key="section.id"
                    :section="{
                        ...section,
                        amenities: linkedAmenitiesBySection[String(section.id)] ?? section.amenities,
                    }"
                    :available-amenities="availableAmenitiesBySection[String(section.id)] ?? []"
                    :assigned-student="assignedStudentsBySection[String(section.id)]"
                    :can-manage="canEditRoom"
                    :saving="savingSectionId === String(section.id)"
                    @link="linkAmenity(section.id, $event)"
                    @unlink="unlinkAmenity(section.id, $event)"
                />
            </div>
        </div>

        <CreateEditRoom :default-hostel-id="room.hostel.id" />
    </PageContainer>
</template>
