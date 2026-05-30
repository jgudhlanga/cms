<script setup lang="ts">
import { scoreBarColor } from '@/composables/students/studentProgrammeDisplay';
import type { StudentProgrammeModuleCourseWork } from '@/types/students';
import { ClipboardList } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    courseWork: StudentProgrammeModuleCourseWork;
}

const props = defineProps<Props>();

const formatMark = (value: number | null | undefined): string =>
    value !== null && value !== undefined ? String(value) : '—';

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
    <div class="border-t border-border bg-background/60 px-5 py-4">
        <div class="mb-3 flex items-center gap-2">
            <ClipboardList class="h-4 w-4 text-primary" stroke-width="1.75" />
            <h4 class="text-sm font-bold tracking-tight text-foreground">
                {{ $t('students.course_work_marks') }}
            </h4>
        </div>

        <div
            v-if="!hasCapturedMarks"
            class="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground"
        >
            {{ $t('students.course_work_no_marks') }}
        </div>

        <template v-else>
            <div class="overflow-hidden rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50 text-left text-[0.7rem] font-semibold uppercase tracking-wide text-muted-foreground">
                            <th class="px-4 py-2.5">{{ $t('students.assessment') }}</th>
                            <th class="px-4 py-2.5 text-right">{{ $t('academic_calendar.course_work_mark') }}</th>
                            <th class="hidden px-4 py-2.5 text-right sm:table-cell">
                                {{ $t('academic_calendar.course_work_weighted_mark') }}
                            </th>
                            <th class="hidden px-4 py-2.5 md:table-cell">{{ $t('academic_calendar.course_work_remark') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="component in courseWork.aggregation.components"
                            :key="component.assessmentTypeId"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium text-foreground">
                                {{ component.assessmentTypeName }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono tabular-nums">
                                {{ formatMark(component.rawMark) }}
                            </td>
                            <td class="hidden px-4 py-3 text-right font-mono tabular-nums text-muted-foreground sm:table-cell">
                                {{ formatWeighted(component.weightedMark) }}
                            </td>
                            <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">
                                {{
                                    courseWork.assessments.find(
                                        (item) => item.assessmentTypeId === component.assessmentTypeId,
                                    )?.remark ?? '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ $t('academic_calendar.course_work_total_60') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums tracking-tight text-foreground">
                        {{ formatMark(courseWorkTotal) }}
                        <span class="text-base font-medium text-muted-foreground">/ 60</span>
                    </p>
                    <div
                        v-if="courseWorkPercent !== null"
                        class="mt-2"
                    >
                        <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="scoreBarColor(courseWorkPercent)"
                                :style="{ width: `${courseWorkPercent}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-dashed border-border bg-muted/20 px-4 py-3">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ $t('students.final_grade') }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-muted-foreground">
                        {{ $t('students.final_grade_pending_exam') }}
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>
