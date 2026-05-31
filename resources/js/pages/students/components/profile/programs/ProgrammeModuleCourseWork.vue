<script setup lang="ts">
import { scoreBarColor } from '@/composables/students/studentProgrammeDisplay';
import type { StudentProgrammeModuleCourseWork } from '@/types/students';
import { computed } from 'vue';

interface Props {
    courseWork: StudentProgrammeModuleCourseWork;
}

const props = defineProps<Props>();

const formatMark = (value: number | null | undefined): string =>
    value !== null && value !== undefined ? String(value) : '—';

const isMissingMark = (value: number | null | undefined): boolean =>
    value === null || value === undefined;

const formatWeighted = (value: number | null | undefined): string =>
    value !== null && value !== undefined ? value.toFixed(1) : '—';

const courseWorkTotal = computed(() => props.courseWork.aggregation.courseWorkTotal60);
const courseWorkPercent = computed(() => {
    const total = courseWorkTotal.value;

    if (total === null || total === undefined) {
        return null;
    }

    return Math.round((total / 60) * 100);
});

const hasCapturedMarks = computed(() =>
    props.courseWork.assessments.some((assessment) => assessment.mark !== null),
);
</script>

<template>
    <div class="mt-1">
        <p class="mb-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $t('students.course_work_marks') }}
        </p>

        <p
            v-if="!hasCapturedMarks"
            class="text-xs text-amber-500"
        >
            {{ $t('students.course_work_no_marks') }}
        </p>

        <template v-else>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-border text-left text-[0.62rem] font-semibold uppercase tracking-wide text-muted-foreground">
                            <th class="py-1 pr-2">{{ $t('students.assessment') }}</th>
                            <th class="px-2 py-1 text-right">{{ $t('academic_calendar.course_work_mark') }}</th>
                            <th class="hidden px-2 py-1 text-right sm:table-cell">
                                {{ $t('academic_calendar.course_work_weighted_mark') }}
                            </th>
                            <th class="hidden py-1 pl-2 md:table-cell">{{ $t('academic_calendar.course_work_remark') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="component in courseWork.aggregation.components"
                            :key="component.assessmentTypeId"
                        >
                            <td class="py-1 pr-2 font-medium text-foreground">
                                {{ component.assessmentTypeName }}
                            </td>
                            <td
                                class="px-2 py-1 text-right font-mono tabular-nums"
                                :class="{ 'text-amber-500': isMissingMark(component.rawMark) }"
                            >
                                {{ formatMark(component.rawMark) }}
                            </td>
                            <td
                                class="hidden px-2 py-1 text-right font-mono tabular-nums sm:table-cell"
                                :class="isMissingMark(component.weightedMark) ? 'text-amber-500' : 'text-muted-foreground'"
                            >
                                {{ formatWeighted(component.weightedMark) }}
                            </td>
                            <td class="hidden py-1 pl-2 md:table-cell">
                                <span
                                    :class="{
                                        'text-amber-500': !(courseWork.assessments.find(
                                            (item) => item.assessmentTypeId === component.assessmentTypeId,
                                        )?.remark),
                                    }"
                                >
                                    {{
                                        courseWork.assessments.find(
                                            (item) => item.assessmentTypeId === component.assessmentTypeId,
                                        )?.remark ?? '—'
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-2 flex flex-wrap items-baseline gap-x-4 gap-y-1 text-xs">
                <span>
                    <span class="text-muted-foreground">{{ $t('academic_calendar.course_work_total_60') }}:</span>
                    <span
                        class="ml-1 font-semibold tabular-nums"
                        :class="{ 'text-amber-500': isMissingMark(courseWorkTotal) }"
                    >
                        {{ formatMark(courseWorkTotal) }}/60
                    </span>
                </span>
                <span class="text-muted-foreground">
                    {{ $t('students.final_grade_pending_exam') }}
                </span>
            </div>

            <div
                v-if="courseWorkPercent !== null"
                class="mt-1.5"
            >
                <div class="h-[2px] overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="scoreBarColor(courseWorkPercent)"
                        :style="{ width: `${courseWorkPercent}%` }"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
