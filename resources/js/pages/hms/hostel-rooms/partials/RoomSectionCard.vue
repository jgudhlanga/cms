<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { avatarColorForName, studentInitials } from '@/lib/hms/hostelRoomDisplay';
import { amenityIconName } from '@/lib/hms/roomSectionDisplay';
import { computed } from 'vue';

type StudentCard = {
    id: number | string;
    name: string;
    studentNumber?: string | null;
    course?: string | null;
};

type SectionAmenity = {
    id: number | string;
    name: string;
    slug?: string | null;
};

type SectionRecord = {
    id: number | string;
    name: string;
    amenities: SectionAmenity[];
};

interface Props {
    section: SectionRecord;
    availableAmenities?: SectionAmenity[];
    assignedStudent?: StudentCard | null;
    canManage?: boolean;
    saving?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    availableAmenities: () => [],
    assignedStudent: null,
    canManage: false,
    saving: false,
});

const emit = defineEmits<{
    link: [amenityId: number];
    unlink: [amenityId: number];
}>();

const studentAvatar = computed(() => ({
    initials: studentInitials(props.assignedStudent?.name),
    color: avatarColorForName(props.assignedStudent?.name),
}));

const iconForAmenity = (amenity: SectionAmenity) => amenityIconName(amenity.slug ?? amenity.name);

const onLink = (amenityId: number | string) => {
    if (!props.canManage || props.saving) {
        return;
    }

    emit('link', Number(amenityId));
};

const onUnlink = (amenityId: number | string) => {
    if (!props.canManage || props.saving) {
        return;
    }

    emit('unlink', Number(amenityId));
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div class="flex items-start justify-between gap-3 border-b border-border/80 px-4 py-3.5 sm:px-5">
            <div class="flex min-w-0 items-center gap-2.5">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary"
                >
                    {{ section.name }}
                </div>
                <div class="min-w-0">
                    <h3 class="truncate text-base font-bold text-foreground">
                        {{ $t('hms.room_section_name', { name: section.name }) }}
                    </h3>
                </div>
            </div>
            <span
                class="shrink-0 rounded-full border border-border bg-muted/40 px-2.5 py-1 text-xs font-semibold text-muted-foreground"
            >
                {{ $t('hms.section_total_amenities', { count: section.amenities.length }) }}
            </span>
        </div>

        <div class="space-y-4 px-4 py-4 sm:px-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <template v-if="assignedStudent">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                            :style="{ background: studentAvatar.color }"
                        >
                            {{ studentAvatar.initials }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                {{ $t('hms.section_occupant') }}
                            </p>
                            <p class="truncate text-sm font-semibold text-foreground">{{ assignedStudent.name }}</p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ assignedStudent.studentNumber || '---' }}
                                <span v-if="assignedStudent.course"> · {{ assignedStudent.course }}</span>
                            </p>
                        </div>
                    </template>
                    <template v-else>
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                        >
                            <BaseIcon :name="IconName.user" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                {{ $t('hms.section_occupant') }}
                            </p>
                            <p class="text-sm font-medium text-foreground">{{ $t('hms.section_available') }}</p>
                        </div>
                    </template>
                </div>

                <BaseButton
                    v-if="canManage && !assignedStudent"
                    type="button"
                    :size="ButtonSize.sm"
                    :variant="ColorVariant.primary_outline"
                    classes="rounded-full shrink-0"
                    disabled
                >
                    <BaseIcon :name="IconName.add" class="h-3.5 w-3.5" />
                    {{ $t('hms.section_assign') }}
                </BaseButton>
            </div>

            <div class="space-y-2.5">
                <p class="inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                    <BaseIcon :name="IconName.check" class="h-3.5 w-3.5 text-primary" />
                    {{ $t('hms.section_linked_amenities') }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="amenity in section.amenities"
                        :key="amenity.id"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-primary/15 bg-primary/5 px-2.5 py-1.5 text-xs font-medium text-foreground"
                    >
                        <BaseIcon :name="iconForAmenity(amenity)" class="h-3.5 w-3.5 text-primary" />
                        {{ amenity.name }}
                        <button
                            v-if="canManage"
                            type="button"
                            class="ml-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full text-primary transition hover:bg-primary/10 disabled:opacity-50"
                            :disabled="saving"
                            :aria-label="$t('trans.delete')"
                            @click="onUnlink(amenity.id)"
                        >
                            <BaseIcon :name="IconName.close" class="h-3 w-3" />
                        </button>
                    </span>
                    <span
                        v-if="section.amenities.length === 0"
                        class="rounded-xl border border-dashed border-border px-3 py-1.5 text-xs italic text-muted-foreground"
                    >
                        {{ $t('hms.no_section_amenities') }}
                    </span>
                </div>
            </div>

            <div v-if="availableAmenities.length > 0" class="space-y-2.5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                    {{ $t('hms.section_available_amenities') }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="amenity in availableAmenities"
                        :key="amenity.id"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-background px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canManage || saving"
                        @click="onLink(amenity.id)"
                    >
                        <BaseIcon :name="iconForAmenity(amenity)" class="h-3.5 w-3.5 text-muted-foreground" />
                        {{ amenity.name }}
                        <BaseIcon :name="IconName.add" class="h-3 w-3 text-primary" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
