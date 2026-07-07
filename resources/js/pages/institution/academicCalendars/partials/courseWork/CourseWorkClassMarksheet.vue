<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import Empty from '@/components/core/util/Empty.vue';
import { useCourseWorkClassMarksheet } from '@/composables/academicCalendars/useCourseWorkClassMarksheet';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import { isCourseWorkMarkInputInvalid, parseCourseWorkMark } from '@/lib/course-work';
import type { CourseWorkAssessment, CourseWorkStudent } from '@/types/course-work';
import { trans } from 'laravel-vue-i18n';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
        classConfigId: number;
        canCreate: boolean;
        canUpdate: boolean;
        canExport: boolean;
        canImport?: boolean;
        courseWorkExportUrl: (moduleId: number, format: 'xlsx' | 'pdf', strict?: boolean) => string;
        courseWorkImportUrl?: (moduleId: number) => string;
    }>();

const {
    selectedModuleId,
    selectedModuleCaptureMarkOnly,
    moduleOptions,
    selectedModuleSummary,
    moduleStudents,
    assessmentTypes,
    loading,
    error,
    loadTree,
    saveMark,
    isSaving,
    findAssessment,
} = useCourseWorkClassMarksheet({ classConfigId: props.classConfigId });

const draftMarks = ref<Record<string, string>>({});

const draftKey = (studentEnrolmentId: number, assessmentTypeId?: number | null): string =>
    assessmentTypeId != null
        ? `${studentEnrolmentId}:${assessmentTypeId}`
        : `${studentEnrolmentId}:mark-only`;

const syncDraftsFromStudents = (): void => {
    const next: Record<string, string> = {};

    for (const student of moduleStudents.value) {
        if (selectedModuleCaptureMarkOnly.value) {
            const moduleMark = student.moduleMark;
            next[draftKey(student.studentEnrolmentId)] =
                moduleMark?.mark !== null && moduleMark?.mark !== undefined ? String(moduleMark.mark) : '';

            continue;
        }

        for (const assessment of student.assessments) {
            next[draftKey(student.studentEnrolmentId, assessment.assessmentTypeId)] =
                assessment.mark !== null ? String(assessment.mark) : '';
        }
    }

    draftMarks.value = next;
};

watch(moduleStudents, syncDraftsFromStudents, { immediate: true });

const completionLabel = computed(() => {
    const summary = selectedModuleSummary.value;
    if (!summary) {
        return null;
    }

    return trans('academic_calendar.course_work_export_complete', {
        complete: String(summary.completeCount),
        total: String(summary.studentCount),
    });
});

const canEditAssessment = (assessment: CourseWorkAssessment): boolean =>
    assessment.markId != null ? props.canUpdate : props.canCreate;

const canEditModuleMark = (student: CourseWorkStudent): boolean =>
    student.moduleMark?.markId != null ? props.canUpdate : props.canCreate;

const onModuleMarkBlur = async (student: CourseWorkStudent): Promise<void> => {
    if (!canEditModuleMark(student) || selectedModuleId.value === null) {
        return;
    }

    const key = draftKey(student.studentEnrolmentId);
    const rawValue = draftMarks.value[key] ?? '';

    if (isCourseWorkMarkInputInvalid(rawValue)) {
        errorAlert(trans('academic_calendar.course_work_mark_invalid'));
        draftMarks.value[key] =
            student.moduleMark?.mark !== null && student.moduleMark?.mark !== undefined
                ? String(student.moduleMark.mark)
                : '';

        return;
    }

    const nextMark = parseCourseWorkMark(rawValue);
    const currentMark = student.moduleMark?.mark ?? null;

    if (nextMark === currentMark) {
        return;
    }

    await saveMark({
        markId: student.moduleMark?.markId ?? null,
        studentEnrolmentId: student.studentEnrolmentId,
        courseSyllabusModuleId: selectedModuleId.value,
        assessmentTypeId: null,
        mark: nextMark,
        remark: student.moduleMark?.remark ?? null,
    });
};

const onMarkBlur = async (student: CourseWorkStudent, assessment: CourseWorkAssessment): Promise<void> => {
    if (!canEditAssessment(assessment) || selectedModuleId.value === null) {
        return;
    }

    const key = draftKey(student.studentEnrolmentId, assessment.assessmentTypeId);
    const rawValue = draftMarks.value[key] ?? '';

    if (isCourseWorkMarkInputInvalid(rawValue)) {
        errorAlert(trans('academic_calendar.course_work_mark_invalid'));
        draftMarks.value[key] = assessment.mark !== null ? String(assessment.mark) : '';

        return;
    }

    const nextMark = parseCourseWorkMark(rawValue);
    const currentMark = assessment.mark;

    if (nextMark === currentMark) {
        return;
    }

    await saveMark({
        markId: assessment.markId,
        studentEnrolmentId: student.studentEnrolmentId,
        courseSyllabusModuleId: selectedModuleId.value,
        assessmentTypeId: assessment.assessmentTypeId,
        mark: nextMark,
        remark: assessment.remark,
    });
};

const formatTotal = (value: number | null | undefined): string =>
    value !== null && value !== undefined ? String(value) : '—';

onMounted(() => {
    void loadTree();
});
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-foreground">
                {{ $t('academic_calendar.course_work_marksheet') }}
            </h2>
            <p v-if="completionLabel" class="text-sm text-muted-foreground">{{ completionLabel }}</p>
        </div>

        <p v-if="loading" class="text-sm text-muted-foreground">{{ $t('academic_calendar.course_work_loading') }}</p>
        <p v-else-if="error" class="text-sm text-destructive">{{ $t(error) }}</p>

        <Empty
            v-else-if="moduleOptions.length === 0"
            :message="$t('academic_calendar.course_work_no_modules')"
        />

        <template v-else>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:gap-4">
                <div class="min-w-0 flex-1 space-y-1">
                    <label class="text-xs font-bold uppercase text-muted-foreground" for="course-work-module">
                        {{ $t('academic_calendar.course_work_marksheet_module') }}
                    </label>
                    <select
                        id="course-work-module"
                        v-model.number="selectedModuleId"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option v-for="option in moduleOptions" :key="option.moduleId" :value="option.moduleId">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div v-if="(canExport || canImport) && selectedModuleId" class="flex shrink-0 flex-wrap gap-2">
                    <InertiaLink
                        v-if="canImport && courseWorkImportUrl"
                        :href="courseWorkImportUrl(selectedModuleId)"
                        class="inline-flex"
                    >
                        <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                            {{ $t('academic_calendar.course_work_import') }}
                        </BaseButton>
                    </InertiaLink>
                    <a
                        v-if="canExport"
                        :href="courseWorkExportUrl(selectedModuleId, 'xlsx')"
                        class="inline-flex"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                            {{ $t('academic_calendar.course_work_export_excel') }}
                        </BaseButton>
                    </a>
                    <a
                        v-if="canExport"
                        :href="courseWorkExportUrl(selectedModuleId, 'pdf')"
                        class="inline-flex"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.sm">
                            {{ $t('academic_calendar.course_work_export_pdf') }}
                        </BaseButton>
                    </a>
                </div>
            </div>

            <p v-if="!selectedModuleCaptureMarkOnly" class="text-xs text-muted-foreground">
                {{ $t('academic_calendar.course_work_phase2_note') }}
            </p>

            <Empty
                v-if="moduleStudents.length === 0"
                :message="$t('academic_calendar.course_work_no_students')"
            />

            <div v-else-if="selectedModuleCaptureMarkOnly" class="overflow-x-auto rounded-lg border border-border">
                <table class="j-table min-w-full">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                            <th class="j-th text-left">{{ $t('academic_calendar.course_work_candidate_number') }}</th>
                            <th class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                            <th class="j-th text-center">{{ $t('academic_calendar.course_work_mark') }}</th>
                            <th class="j-th text-left">{{ $t('academic_calendar.course_work_remark') }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr v-for="student in moduleStudents" :key="student.studentEnrolmentId" class="j-tr">
                            <td class="j-td">{{ student.name }}</td>
                            <td class="j-td">{{ (student as { candidateNumber?: string | null }).candidateNumber ?? student.studentNumber ?? '---' }}</td>
                            <td class="j-td font-mono text-xs">{{ student.studentNumber ?? $t('students.not_available') }}</td>
                            <td class="j-td text-center">
                                <input
                                    v-model="draftMarks[draftKey(student.studentEnrolmentId)]"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="1"
                                    class="mx-auto h-8 w-16 rounded-md border border-input bg-background px-2 text-center text-sm"
                                    :disabled="
                                        !canEditModuleMark(student) ||
                                        isSaving(student.studentEnrolmentId, selectedModuleId!)
                                    "
                                    @blur="onModuleMarkBlur(student)"
                                />
                            </td>
                            <td class="j-td text-sm text-muted-foreground">
                                {{ student.moduleMark?.remark ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="overflow-x-auto rounded-lg border border-border">
                <table class="j-table min-w-full">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                            <th class="j-th text-left">{{ $t('academic_calendar.course_work_candidate_number') }}</th>
                            <th class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                            <th
                                v-for="type in assessmentTypes"
                                :key="type.id"
                                class="j-th text-center text-xs"
                            >
                                {{ type.name }}
                                <span v-if="type.weightPercent" class="block font-normal text-muted-foreground">
                                    ({{ type.weightPercent }}%)
                                </span>
                            </th>
                            <th class="j-th text-center">{{ $t('academic_calendar.course_work_total_60') }}</th>
                            <th class="j-th text-left">{{ $t('academic_calendar.course_work_remark') }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr v-for="student in moduleStudents" :key="student.studentEnrolmentId" class="j-tr">
                            <td class="j-td">{{ student.name }}</td>
                            <td class="j-td">{{ student.candidateNumber ?? '---' }}</td>
                            <td class="j-td font-mono text-xs">{{ student.studentNumber ?? $t('students.not_available') }}</td>
                            <td
                                v-for="type in assessmentTypes"
                                :key="`${student.studentEnrolmentId}-${type.id}`"
                                class="j-td text-center"
                            >
                                <template v-if="findAssessment(student, type.id)">
                                    <input
                                        v-model="draftMarks[draftKey(student.studentEnrolmentId, type.id)]"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="1"
                                        class="mx-auto h-8 w-16 rounded-md border border-input bg-background px-2 text-center text-sm"
                                        :disabled="
                                            !canEditAssessment(findAssessment(student, type.id)!) ||
                                            isSaving(student.studentEnrolmentId, selectedModuleId!, type.id)
                                        "
                                        @blur="onMarkBlur(student, findAssessment(student, type.id)!)"
                                    />
                                </template>
                            </td>
                            <td class="j-td text-center font-semibold">
                                {{ formatTotal(student.aggregation?.courseWorkTotal60) }}
                            </td>
                            <td class="j-td text-sm text-muted-foreground">
                                {{ student.aggregation?.remark ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </section>
</template>
