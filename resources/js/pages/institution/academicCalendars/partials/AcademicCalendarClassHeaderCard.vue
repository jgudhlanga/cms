<script setup lang="ts">
import AcademicCalendarClassTutorBadge from '@/components/academicCalendars/AcademicCalendarClassTutorBadge.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import LevelCodeBadge from '@/components/core/util/LevelCodeBadge.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { formatLevelBadge, shortClassNumberLabel } from '@/lib/levelBadge';
import type { ClassConfig, ClassTutorSummary } from '@/types/academic-calendar';
import { trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    description: string | null;
    studentCount: number;
    tutor: ClassTutorSummary;
    canUpdate: boolean;
    canAssignStaffing?: boolean;
    classConfig?: ClassConfig | null;
}>();

const emit = defineEmits<{
    edit: [];
    assignTutor: [];
    removeTutor: [];
}>();

const levelName = computed(() => String(props.classConfig?.attributes?.departmentLevel ?? '').trim());
const badgeLabel = computed(() =>
    levelName.value !== '' ? formatLevelBadge(levelName.value) : shortClassNumberLabel(props.title),
);
const codesLabel = computed(() =>
    (props.classConfig?.attributes?.courseSyllabusCodes ?? []).filter((code) => String(code).trim() !== '').join(', '),
);
const modeLabel = computed(() => String(props.classConfig?.attributes?.modeOfStudy ?? '').trim());
const metaBits = computed(() =>
    [
        codesLabel.value,
        modeLabel.value,
        `${props.studentCount} ${trans_choice('trans.student', props.studentCount)}`,
    ].filter((bit) => bit !== ''),
);
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-border/60 bg-muted/20"
        role="group"
        :aria-label="title"
    >
        <div class="flex flex-col gap-2 p-2.5 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-3 sm:gap-y-2">
            <LevelCodeBadge :label="badgeLabel" :title="levelName || title" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-foreground">{{ title }}</p>
                <p v-if="metaBits.length > 0" class="truncate text-[11px] text-muted-foreground">
                    {{ metaBits.join(' · ') }}
                </p>
            </div>
            <AcademicCalendarClassTutorBadge
                :tutor="tutor"
                :can-assign="canAssignStaffing === true"
                compact
                @assign="emit('assignTutor')"
                @remove="emit('removeTutor')"
            />
            <div v-if="canUpdate" class="flex shrink-0 flex-wrap items-center gap-1.5">
                <BaseButton
                    type="button"
                    :size="ButtonSize.xs"
                    :variant="ColorVariant.shade"
                    classes="inline-flex items-center gap-1 rounded-full"
                    @click="emit('edit')"
                >
                    <BaseIcon :name="IconName.edit" class="h-3.5 w-3.5" />
                    <span>{{ $t('trans.edit') }}</span>
                </BaseButton>
            </div>
        </div>
        <div v-if="$slots.default" class="border-t border-border/50">
            <slot />
        </div>
    </div>
</template>
