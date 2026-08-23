<script setup lang="ts">
import LevelCodeBadge from '@/components/core/util/LevelCodeBadge.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import { useSemestersByCalendarType } from '@/composables/academicCalendars/useSemestersByCalendarType';
import { formatLevelBadge } from '@/lib/levelBadge';
import type { ClassConfig, ClassStaffingSummary } from '@/types/academic-calendar';
import { router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, watch } from 'vue';

const props = defineProps<{
    title: string;
    classConfig: ClassConfig | null;
    staffingSummary: ClassStaffingSummary;
    selectedSemesterId: number | null;
    calendarType: 'term' | 'semester' | 'abma';
    semesterConfigHasSyllabi: boolean;
}>();

const { yearOptions, yearOptionsLoading, loadYearOptions } = useSemestersByCalendarType();

onMounted(() => {
    void loadYearOptions(props.calendarType);
});

watch(
    () => props.calendarType,
    (calendarType) => {
        void loadYearOptions(calendarType);
    },
);

const selectedSemester = computed({
    get: () => (props.selectedSemesterId != null ? String(props.selectedSemesterId) : ''),
    set: (value: string) => {
        const currentUrl = new URL(window.location.href);

        if (value === '') {
            currentUrl.searchParams.delete('semester_id');
        } else {
            currentUrl.searchParams.set('semester_id', value);
        }

        router.get(currentUrl.pathname + currentUrl.search, {}, { preserveScroll: true, preserveState: false });
    },
});

const courseName = computed(() => props.classConfig?.attributes?.departmentCourse ?? props.title);
const levelName = computed(() => String(props.classConfig?.attributes?.departmentLevel ?? '').trim());
const levelBadge = computed(() => (levelName.value !== '' ? formatLevelBadge(levelName.value) : ''));
const codesLabel = computed(() => (props.classConfig?.attributes?.courseSyllabusCodes ?? []).filter((code) => String(code).trim() !== '').join(', '));
const modeLabel = computed(() => String(props.classConfig?.attributes?.modeOfStudy ?? '').trim());
const yearLabel = computed(() => String(props.classConfig?.attributes?.calendarYear ?? '').trim());
const classSizeLabel = computed(() => {
    const size = props.classConfig?.attributes?.studentsPerClass;
    if (size == null || String(size).trim() === '') {
        return '';
    }

    return `${trans_choice('academic_calendar.class_unit_size', 1)} ${String(size)}`;
});

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

const showSemesterHelper = computed(() => props.selectedSemesterId == null);
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
            v-if="selectedSemesterId != null"
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
        <div class="min-w-0 sm:w-48">
            <BaseSelect
                v-model="selectedSemester"
                label=""
                :options="yearOptions"
                :loading="yearOptionsLoading"
                :placeholder="$t('trans.select')"
                :is-clearable="true"
                :vertical-layout="false"
                class="min-w-0 w-full"
            />
        </div>
        <p v-if="showSemesterHelper" class="w-full text-[11px] text-muted-foreground">
            {{ $t('academic_calendar.select_semester_for_modules') }}
        </p>
        <p
            v-else-if="selectedSemesterId != null && !semesterConfigHasSyllabi"
            class="w-full text-[11px] text-amber-700"
        >
            {{ $t('academic_calendar.semester_config_missing') }}
        </p>
    </div>
</template>
