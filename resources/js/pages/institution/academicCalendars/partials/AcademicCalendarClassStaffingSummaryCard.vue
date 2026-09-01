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
        <span
            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium"
            :class="
                tutorsComplete
                    ? 'border-green-200 bg-green-50 text-green-700'
                    : staffingSummary.classCount > 0
                      ? 'border-amber-200 bg-amber-50 text-amber-800'
                      : 'border-border bg-muted text-muted-foreground'
            "
        >
            {{ tutorsProgressLabel }}
        </span>
        <span
            v-if="hasPeriod"
            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium"
            :class="
                modulesComplete
                    ? 'border-green-200 bg-green-50 text-green-700'
                    : staffingSummary.modulesTotal > 0
                      ? 'border-amber-200 bg-amber-50 text-amber-800'
                      : 'border-border bg-muted text-muted-foreground'
            "
        >
            {{ modulesProgressLabel }}
        </span>
        <p
            v-if="hasPeriod && !semesterConfigHasSyllabi"
            class="w-full text-[11px] text-amber-700"
        >
            {{ $t('academic_calendar.semester_config_missing') }}
        </p>
    </div>
</template>
