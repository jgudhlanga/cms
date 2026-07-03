<script setup lang="ts">
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useAcademicYearOptionsByCalendarType } from '@/composables/academicCalendars/useAcademicYearOptionsByCalendarType';
import type { ClassConfig, ClassStaffingSummary } from '@/types/academic-calendar';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, watch } from 'vue';

const props = defineProps<{
    title: string;
    classConfig: ClassConfig | null;
    staffingSummary: ClassStaffingSummary;
    selectedAcademicYearOptionId: number | null;
    calendarType: 'term' | 'semester' | 'abma';
    semesterConfigHasSyllabi: boolean;
}>();

const { yearOptions, yearOptionsLoading, loadYearOptions } = useAcademicYearOptionsByCalendarType();

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
    get: () => (props.selectedAcademicYearOptionId != null ? String(props.selectedAcademicYearOptionId) : ''),
    set: (value: string) => {
        const currentUrl = new URL(window.location.href);

        if (value === '') {
            currentUrl.searchParams.delete('academic_year_option_id');
        } else {
            currentUrl.searchParams.set('academic_year_option_id', value);
        }

        router.get(currentUrl.pathname + currentUrl.search, {}, { preserveScroll: true, preserveState: false });
    },
});

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

const showSemesterHelper = computed(() => props.selectedAcademicYearOptionId == null);
</script>

<template>
    <BaseCard :title="title">
        <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                <LabelValue
                    :label="$tChoice('syllabus.course_code', 2)"
                    :value="classConfig?.attributes?.courseSyllabusCodes?.join(', ') ?? '---'"
                />
                <LabelValue :label="$tChoice('trans.level', 1)" :value="classConfig?.attributes?.departmentLevel ?? '---'" />
                <LabelValue :label="$tChoice('general.mode', 1)" :value="classConfig?.attributes?.modeOfStudy ?? '---'" />
                <LabelValue
                    :label="$tChoice('academic_calendar.class_unit_size', 1)"
                    :value="String(classConfig?.attributes?.studentsPerClass ?? '---')"
                />
                <LabelValue :label="$tChoice('trans.class', 2)" :value="String(staffingSummary.classCount)" />
                <BaseSelect
                    v-model="selectedSemester"
                    :label="$t('academic_calendar.semester')"
                    :options="yearOptions"
                    :loading="yearOptionsLoading"
                    :placeholder="$t('trans.select')"
                    :is-clearable="true"
                />
            </div>

            <div class="flex flex-col gap-2 rounded-xl border border-border bg-muted/30 p-4">
                <div class="flex flex-wrap items-center gap--2 gap-2">
                    <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
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
                        v-if="selectedAcademicYearOptionId != null"
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
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
                </div>
                <p v-if="showSemesterHelper" class="text-sm text-muted-foreground">
                    {{ $t('academic_calendar.select_semester_for_modules') }}
                </p>
                <p
                    v-else-if="selectedAcademicYearOptionId != null && !semesterConfigHasSyllabi"
                    class="text-sm text-amber-700"
                >
                    {{ $t('academic_calendar.semester_config_missing') }}
                </p>
            </div>
        </div>
    </BaseCard>
</template>
