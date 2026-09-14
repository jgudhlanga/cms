<script setup lang="ts">
import LevelCodeBadge from '@/components/core/util/LevelCodeBadge.vue';
import { formatLevelBadge } from '@/lib/levelBadge';
import type { ClassConfig, ClassStaffingSummary } from '@/types/academic-calendar';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    classConfig: ClassConfig | null;
    staffingSummary: ClassStaffingSummary;
    selectedSemesterId: number | null;
    semesterConfigHasSyllabi: boolean;
    lecturerInChargeName?: string | null;
    canAssignLecturerInCharge?: boolean;
}>();

const emit = defineEmits<{
    (event: 'assign-lecturer-in-charge'): void;
}>();

const courseName = computed(() => props.classConfig?.attributes?.departmentCourse ?? props.title);
const levelName = computed(() => String(props.classConfig?.attributes?.departmentLevel ?? '').trim());
const levelBadge = computed(() => (levelName.value !== '' ? formatLevelBadge(levelName.value) : ''));
const codesLabel = computed(() => (props.classConfig?.attributes?.courseSyllabusCodes ?? []).filter((code) => String(code).trim() !== '').join(', '));
const modeLabel = computed(() => String(props.classConfig?.attributes?.modeOfStudy ?? '').trim());
const yearLabel = computed(() => String(props.classConfig?.attributes?.calendarYear ?? '').trim());
const periodLabel = computed(() => String(props.classConfig?.attributes?.periodLabel ?? '').trim());
const classSizeLabel = computed(() => {
    const size = props.classConfig?.attributes?.studentsPerClass;
    if (size == null || String(size).trim() === '') {
        return '';
    }

    return `${trans_choice('academic_calendar.class_unit_size', 1)} ${String(size)}`;
});

const hasPeriod = computed(
    () =>
        props.selectedSemesterId != null
        || props.classConfig?.attributes?.semesterId != null
        || props.classConfig?.attributes?.programmeSemesterId != null
        || periodLabel.value !== '',
);

const metaBits = computed(() =>
    [codesLabel.value, modeLabel.value, yearLabel.value !== '' ? yearLabel.value : '', classSizeLabel.value].filter((bit) => bit !== ''),
);

const tutorsProgressLabel = computed(() =>
    trans('academic_calendar.staffing_tutors_progress', {
        assigned: props.staffingSummary.tutorsAssigned,
        total: props.staffingSummary.classCount,
    }),
);

const modulesProgressLabel = computed(() =>
    trans('academic_calendar.staffing_modules_progress', {
        staffed: props.staffingSummary.moduleSlotsStaffed,
        total: props.staffingSummary.modulesTotal,
    }),
);

const tutorsComplete = computed(
    () =>
        props.staffingSummary.classCount > 0
        && props.staffingSummary.tutorsAssigned >= props.staffingSummary.classCount,
);

const modulesComplete = computed(
    () =>
        props.staffingSummary.modulesTotal > 0
        && props.staffingSummary.moduleSlotsStaffed >= props.staffingSummary.modulesTotal,
);

/**
 * Staffing pills carry three states: complete, in progress, and nothing to staff.
 * The green/amber ramps need explicit dark variants — the light-only values these
 * previously used washed out to near-invisible on a dark ground.
 */
const COMPLETE_PILL = 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300';
const PENDING_PILL = 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300';
const EMPTY_PILL = 'border-border bg-muted text-muted-foreground';

const pillClass = (complete: boolean, total: number): string =>
    complete ? COMPLETE_PILL : total > 0 ? PENDING_PILL : EMPTY_PILL;

const tutorsPillClass = computed(() => pillClass(tutorsComplete.value, props.staffingSummary.classCount));
const modulesPillClass = computed(() => pillClass(modulesComplete.value, props.staffingSummary.modulesTotal));
</script>

<template>
    <div
        class="flex flex-col gap-2 rounded-lg border border-border/60 bg-muted/20 p-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-3 sm:gap-y-2"
        role="group"
        :aria-label="courseName"
    >
        <LevelCodeBadge v-if="levelBadge" :label="levelBadge" :title="levelName" />
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-foreground">{{ courseName }}</p>
            <p v-if="metaBits.length > 0" class="truncate text-[11px] text-muted-foreground">
                {{ metaBits.join(' · ') }}
            </p>
        </div>
        <span
            v-if="periodLabel"
            class="inline-flex items-center rounded-full border border-border bg-background px-2 py-0.5 text-[11px] font-medium text-foreground"
        >
            {{ periodLabel }}
        </span>
        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium" :class="tutorsPillClass">
            {{ tutorsProgressLabel }}
        </span>
        <span
            v-if="hasPeriod"
            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium"
            :class="modulesPillClass"
        >
            {{ modulesProgressLabel }}
        </span>
        <span
            v-if="classConfig"
            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium"
            :class="lecturerInChargeName ? COMPLETE_PILL : PENDING_PILL"
        >
            {{ $t('academic_calendar.lecturer_in_charge') }}:
            {{ lecturerInChargeName || $t('academic_calendar.lecturer_in_charge_not_assigned') }}
        </span>
        <button
            v-if="classConfig && canAssignLecturerInCharge"
            type="button"
            class="inline-flex items-center rounded-full border border-border px-2 py-0.5 text-[11px] font-medium text-primary hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            @click="emit('assign-lecturer-in-charge')"
        >
            {{
                lecturerInChargeName
                    ? $t('academic_calendar.lecturer_in_charge_change_action')
                    : $t('academic_calendar.lecturer_in_charge_assign_action')
            }}
        </button>
        <p
            v-if="hasPeriod && !semesterConfigHasSyllabi"
            class="w-full text-[11px] text-amber-700 dark:text-amber-400"
        >
            {{ $t('academic_calendar.semester_config_missing') }}
        </p>
    </div>
</template>
