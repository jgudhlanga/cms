<script setup lang="ts">
import AcademicCalendarClassTutorBadge from '@/components/academicCalendars/AcademicCalendarClassTutorBadge.vue';
import LevelCodeBadge from '@/components/core/util/LevelCodeBadge.vue';
import { shortClassNumberLabel } from '@/lib/levelBadge';
import type { AcademicCalendarClassPreview } from '@/types/academic-calendar';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    classPreview: AcademicCalendarClassPreview;
    showUrl?: string | null;
    canAssignStaffing?: boolean;
    showModuleStaffing?: boolean;
}>();

const emit = defineEmits<{
    assignTutor: [classId: number, staffId?: number | null];
    removeTutor: [classId: number];
}>();

const isSavedClass = computed(() => props.classPreview.academicCalendarClassId != null);
const isClickable = computed(() => props.showUrl != null && props.showUrl !== '');
const shortName = computed(() => shortClassNumberLabel(props.classPreview.name));
const maleCount = computed(() => props.classPreview.genderCounts?.male ?? 0);
const femaleCount = computed(() => props.classPreview.genderCounts?.female ?? 0);
const moduleStaffing = computed(() => props.classPreview.moduleStaffing);
const moduleShare = computed(() => {
    const total = Number(moduleStaffing.value?.total ?? 0);
    const staffed = Number(moduleStaffing.value?.staffed ?? 0);
    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((staffed / total) * 100));
});

const rowClass = computed(() => {
    const base =
        'flex min-h-9 flex-wrap items-center gap-x-2 gap-y-1.5 rounded-lg bg-primary/5 px-2.5 py-1.5 transition-colors';

    return isClickable.value ? `${base} cursor-pointer hover:bg-primary/10` : `${base} opacity-80`;
});

const onAssignTutor = (): void => {
    if (props.classPreview.academicCalendarClassId == null) {
        return;
    }

    emit('assignTutor', props.classPreview.academicCalendarClassId, props.classPreview.tutor?.id ?? null);
};

const onRemoveTutor = (): void => {
    if (props.classPreview.academicCalendarClassId == null) {
        return;
    }

    emit('removeTutor', props.classPreview.academicCalendarClassId);
};

const onCardClick = (): void => {
    if (!isClickable.value || props.showUrl == null) {
        return;
    }

    router.visit(props.showUrl);
};
</script>

<template>
    <div
        :class="rowClass"
        :role="isClickable ? 'link' : undefined"
        :tabindex="isClickable ? 0 : undefined"
        :title="classPreview.name"
        @click="onCardClick"
        @keydown.enter.prevent="onCardClick"
        @keydown.space.prevent="onCardClick"
    >
        <LevelCodeBadge :label="shortName" :title="classPreview.name" />
        <div v-if="isSavedClass" class="min-w-0" @click.stop.prevent>
            <AcademicCalendarClassTutorBadge
                :tutor="classPreview.tutor ?? null"
                :can-assign="canAssignStaffing === true"
                compact
                @assign="onAssignTutor"
                @remove="onRemoveTutor"
            />
        </div>
        <div
            v-if="showModuleStaffing && moduleStaffing && moduleStaffing.total > 0"
            class="inline-flex min-w-0 items-center gap-1.5 text-[11px] text-muted-foreground"
        >
            <span class="tabular-nums">
                {{
                    $t('academic_calendar.modules_staffed_badge', {
                        staffed: moduleStaffing.staffed,
                        total: moduleStaffing.total,
                    })
                }}
            </span>
            <span class="h-1 w-16 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                <span class="block h-full bg-primary" :style="{ width: `${moduleShare}%` }" />
            </span>
        </div>
        <span class="inline-flex items-center gap-2 text-[11px] text-muted-foreground">
            <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-600" aria-hidden="true" />
                <span class="tabular-nums">{{ maleCount }}</span>
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-pink-500" aria-hidden="true" />
                <span class="tabular-nums">{{ femaleCount }}</span>
            </span>
        </span>
        <span
            class="inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-medium"
            :class="
                isSavedClass
                    ? 'border-green-200 bg-green-50 text-green-700'
                    : 'border-border bg-muted text-muted-foreground'
            "
        >
            {{ isSavedClass ? $t('hms.status_active') : $t('trans.preview') }}
        </span>
        <span class="ml-auto shrink-0 text-sm font-bold tabular-nums text-foreground">
            {{ classPreview.studentCount }}
        </span>
    </div>
</template>
