<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import Empty from '@/components/core/util/Empty.vue';
import { useCourseWorkClassMarksheet } from '@/composables/academicCalendars/useCourseWorkClassMarksheet';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import { isCourseWorkMarkInputInvalid, parseCourseWorkMark } from '@/lib/course-work';
import { gridCellSelector, nextGridCell } from '@/lib/courseWorkGridNavigation';
import type { CourseWorkAssessment, CourseWorkStudent } from '@/types/course-work';
import { LoaderCircle } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

interface ModuleLockWindow {
    assessmentTypeId: number | null;
    status: string;
    statusLabel?: string;
    message: string;
}

const props = defineProps<{
        classConfigId?: number;
        academicCalendarClassId?: number;
        initialModuleId?: number | null;
        allowedModuleIds?: number[] | null;
        canCreate: boolean;
        canUpdate: boolean;
        canExport: boolean;
        canImport?: boolean;
        courseWorkExportUrl: (moduleId: number, format: 'xlsx' | 'pdf', strict?: boolean) => string;
        courseWorkImportUrl?: (moduleId: number) => string;
        studentCourseWorkUrl?: (studentEnrolmentId: number) => string | null;
        moduleLocks?: Record<number, {
            hasEditableCourseWork: boolean;
            allAssessmentTypesLocked: boolean;
            lockedAssessmentTypeIds: number[];
            lockedAssessmentTypeNames: string[];
            readOnlyMessage: string | null;
            windows?: ModuleLockWindow[];
        }>;
    }>();

const {
    selectedModuleId,
    selectedModuleCaptureMarkOnly,
    moduleOptions,
    selectedModuleSummary,
    moduleStudents,
    assessmentTypes,
    loading,
    savingKey,
    error,
    loadTree,
    saveMark,
    isSaving,
    findAssessment,
} = useCourseWorkClassMarksheet(
    props.academicCalendarClassId != null
        ? {
              academicCalendarClassId: props.academicCalendarClassId,
              initialModuleId: props.initialModuleId ?? null,
              allowedModuleIds: props.allowedModuleIds ?? null,
          }
        : {
              classConfigId: props.classConfigId as number,
              initialModuleId: props.initialModuleId ?? null,
              allowedModuleIds: props.allowedModuleIds ?? null,
          },
);

const draftMarks = ref<Record<string, string>>({});
const invalidKeys = ref<Record<string, boolean>>({});
const liveMessage = ref('');

const draftKey = (studentEnrolmentId: number, assessmentTypeId?: number | null): string =>
    assessmentTypeId != null
        ? `${studentEnrolmentId}:${assessmentTypeId}`
        : `${studentEnrolmentId}:mark-only`;

const domSafe = (value: string): string => value.replace(/[^a-zA-Z0-9_-]/g, '-');
const cellErrorId = (key: string): string => `course-work-mark-error-${domSafe(key)}`;
const columnStatusId = (assessmentTypeId: number | null): string => `course-work-column-status-${assessmentTypeId ?? 'module'}`;
const lockMessageId = 'course-work-lock-message';
const keyboardHintId = 'course-work-keyboard-hint';

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
    invalidKeys.value = {};
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

const selectedModuleLabel = computed(
    () => moduleOptions.value.find((option) => option.moduleId === selectedModuleId.value)?.label ?? '',
);

const isSavingAny = computed(() => savingKey.value != null);

const selectedModuleLock = computed(() =>
    selectedModuleId.value !== null ? props.moduleLocks?.[selectedModuleId.value] ?? null : null,
);

const windowFor = (assessmentTypeId: number | null): ModuleLockWindow | null =>
    selectedModuleLock.value?.windows?.find((window) => window.assessmentTypeId === assessmentTypeId) ?? null;

const canEditAssessment = (assessment: CourseWorkAssessment): boolean =>
    !selectedModuleLock.value?.lockedAssessmentTypeIds.includes(assessment.assessmentTypeId)
    && (assessment.markId != null ? props.canUpdate : props.canCreate);

const canEditModuleMark = (student: CourseWorkStudent): boolean =>
    !selectedModuleLock.value?.allAssessmentTypesLocked
    && (student.moduleMark?.markId != null ? props.canUpdate : props.canCreate);

const markLabel = (student: CourseWorkStudent, assessmentName: string): string =>
    trans('academic_calendar.course_work_mark_input_label', { assessment: assessmentName, student: student.name });

const describedBy = (key: string, assessmentTypeId: number | null, locked: boolean): string =>
    [
        invalidKeys.value[key] ? cellErrorId(key) : null,
        windowFor(assessmentTypeId) ? columnStatusId(assessmentTypeId) : null,
        locked && selectedModuleLock.value?.readOnlyMessage ? lockMessageId : null,
        keyboardHintId,
    ]
        .filter(Boolean)
        .join(' ');

const persistMark = async (
    student: CourseWorkStudent,
    assessment: CourseWorkAssessment | null,
    nextMark: number | null,
    draftKeyValue: string,
    previousValue: string,
): Promise<void> => {
    if (selectedModuleId.value === null) {
        return;
    }

    const assessmentName = assessment?.assessmentTypeName ?? trans('academic_calendar.course_work_window_module_mark');
    const saved = await saveMark({
        markId: assessment?.markId ?? student.moduleMark?.markId ?? null,
        studentEnrolmentId: student.studentEnrolmentId,
        courseSyllabusModuleId: selectedModuleId.value,
        assessmentTypeId: assessment?.assessmentTypeId ?? null,
        mark: nextMark,
        remark: assessment?.remark ?? student.moduleMark?.remark ?? null,
    });

    if (saved) {
        successAlert(trans('academic_calendar.course_work_saved'));
        liveMessage.value = trans('academic_calendar.course_work_mark_saved_announcement', {
            assessment: assessmentName,
            mark: nextMark === null ? '' : String(nextMark),
            student: student.name,
        });

        return;
    }

    draftMarks.value[draftKeyValue] = previousValue;
    liveMessage.value = trans('academic_calendar.course_work_mark_save_failed_announcement', {
        assessment: assessmentName,
        student: student.name,
    });
    errorAlert(trans('academic_calendar.course_work_save_failed'));
};

/**
 * Invalid input stays in the cell with an error next to it (announced, and linked via aria-describedby)
 * instead of silently snapping back; Escape restores the saved value.
 */
const markInvalid = (key: string, student: CourseWorkStudent, assessmentName: string): void => {
    invalidKeys.value = { ...invalidKeys.value, [key]: true };
    liveMessage.value = trans('academic_calendar.course_work_mark_invalid_announcement', {
        assessment: assessmentName,
        student: student.name,
    });
};

const clearInvalid = (key: string): void => {
    if (invalidKeys.value[key]) {
        const next = { ...invalidKeys.value };
        delete next[key];
        invalidKeys.value = next;
    }
};

const onModuleMarkBlur = async (student: CourseWorkStudent): Promise<void> => {
    if (!canEditModuleMark(student) || selectedModuleId.value === null) {
        return;
    }

    const key = draftKey(student.studentEnrolmentId);
    const rawValue = draftMarks.value[key] ?? '';
    const previousValue =
        student.moduleMark?.mark !== null && student.moduleMark?.mark !== undefined
            ? String(student.moduleMark.mark)
            : '';

    if (isCourseWorkMarkInputInvalid(rawValue)) {
        markInvalid(key, student, trans('academic_calendar.course_work_window_module_mark'));

        return;
    }

    clearInvalid(key);
    const nextMark = parseCourseWorkMark(rawValue);
    const currentMark = student.moduleMark?.mark ?? null;

    if (nextMark === currentMark) {
        return;
    }

    await persistMark(student, null, nextMark, key, previousValue);
};

const onMarkBlur = async (student: CourseWorkStudent, assessment: CourseWorkAssessment): Promise<void> => {
    if (!canEditAssessment(assessment) || selectedModuleId.value === null) {
        return;
    }

    const key = draftKey(student.studentEnrolmentId, assessment.assessmentTypeId);
    const rawValue = draftMarks.value[key] ?? '';
    const previousValue = assessment.mark !== null ? String(assessment.mark) : '';

    if (isCourseWorkMarkInputInvalid(rawValue)) {
        markInvalid(key, student, assessment.assessmentTypeName);

        return;
    }

    clearInvalid(key);
    const nextMark = parseCourseWorkMark(rawValue);
    const currentMark = assessment.mark;

    if (nextMark === currentMark) {
        return;
    }

    await persistMark(student, assessment, nextMark, key, previousValue);
};

const onCellKeydown = (
    event: KeyboardEvent,
    rowIndex: number,
    columnIndex: number,
    columnCount: number,
    key: string,
    savedValue: string,
): void => {
    if (event.key === 'Escape') {
        draftMarks.value[key] = savedValue;
        clearInvalid(key);

        return;
    }

    const input = event.target as HTMLInputElement;
    const length = input.value.length;
    const next = nextGridCell({
        key: event.key,
        rowIndex,
        columnIndex,
        rowCount: moduleStudents.value.length,
        columnCount,
        caretAtStart: input.selectionStart === 0 && input.selectionEnd === 0,
        caretAtEnd: input.selectionStart === length && input.selectionEnd === length,
        shiftKey: event.shiftKey,
    });

    if (!next) {
        return;
    }

    const target = document.querySelector<HTMLInputElement>(gridCellSelector(next));

    if (target) {
        event.preventDefault();
        target.focus();
        target.select();
    }
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
            <div class="flex flex-wrap items-center gap-3">
                <p v-if="isSavingAny" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                    <LoaderCircle class="h-3.5 w-3.5 animate-spin" aria-hidden="true" />
                    {{ $t('academic_calendar.course_work_saving') }}
                </p>
                <p v-else-if="completionLabel" class="text-sm text-muted-foreground">{{ completionLabel }}</p>
            </div>
        </div>

        <!-- One polite live region for every save, failure and validation message in the grid. -->
        <p class="sr-only" role="status" aria-live="polite">{{ liveMessage }}</p>

        <p v-if="loading" role="status" class="text-sm text-muted-foreground">{{ $t('academic_calendar.course_work_loading') }}</p>
        <p v-else-if="error" role="alert" class="text-sm text-destructive">{{ $t(error) }}</p>

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
                        v-if="canImport && courseWorkImportUrl && !selectedModuleLock?.allAssessmentTypesLocked"
                        :href="courseWorkImportUrl(selectedModuleId)"
                        class="inline-flex"
                    >
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary_outline"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                        >
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
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary_outline"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                        >
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
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary_outline"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                        >
                            {{ $t('academic_calendar.course_work_export_pdf') }}
                        </BaseButton>
                    </a>
                </div>
            </div>

            <p v-if="!selectedModuleCaptureMarkOnly" class="text-xs text-muted-foreground">
                {{ $t('academic_calendar.course_work_phase2_note') }}
            </p>
            <p
                v-if="selectedModuleLock?.readOnlyMessage"
                :id="lockMessageId"
                role="status"
                class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
            >
                {{ selectedModuleLock.readOnlyMessage }}
            </p>
            <p :id="keyboardHintId" class="text-xs text-muted-foreground">
                {{ $t('academic_calendar.course_work_marksheet_keyboard_hint') }}
            </p>

            <Empty
                v-if="moduleStudents.length === 0"
                :message="$t('academic_calendar.course_work_no_students')"
            />

            <div v-else-if="selectedModuleCaptureMarkOnly" class="overflow-x-auto rounded-lg border border-border">
                <table class="j-table min-w-full">
                    <caption class="sr-only">{{ $t('academic_calendar.course_work_marksheet_caption', { module: selectedModuleLabel }) }}</caption>
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th scope="col" class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                            <th scope="col" class="j-th text-left">{{ $t('academic_calendar.course_work_candidate_number') }}</th>
                            <th scope="col" class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                            <th scope="col" class="j-th text-center">
                                {{ $t('academic_calendar.course_work_mark') }}
                                <span v-if="windowFor(null)" :id="columnStatusId(null)" class="block text-[11px] font-normal text-muted-foreground">
                                    {{ windowFor(null)?.statusLabel }}
                                </span>
                            </th>
                            <th scope="col" class="j-th text-left">{{ $t('academic_calendar.course_work_remark') }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr v-for="(student, rowIndex) in moduleStudents" :key="student.studentEnrolmentId" class="j-tr">
                            <th scope="row" class="j-td text-left font-normal">{{ student.name }}</th>
                            <td class="j-td">{{ student.candidateNumber ?? student.studentNumber ?? '---' }}</td>
                            <td class="j-td font-mono text-xs">{{ student.studentNumber ?? $t('students.not_available') }}</td>
                            <td class="j-td text-center">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <input
                                            v-model="draftMarks[draftKey(student.studentEnrolmentId)]"
                                            type="text"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="3"
                                            autocomplete="off"
                                            :data-course-work-cell="`${rowIndex}-0`"
                                            :aria-label="markLabel(student, $t('academic_calendar.course_work_window_module_mark'))"
                                            :aria-describedby="describedBy(draftKey(student.studentEnrolmentId), null, !canEditModuleMark(student))"
                                            :aria-invalid="invalidKeys[draftKey(student.studentEnrolmentId)] ? 'true' : undefined"
                                            :aria-busy="isSaving(student.studentEnrolmentId, selectedModuleId!) ? 'true' : undefined"
                                            :readonly="!canEditModuleMark(student) || isSaving(student.studentEnrolmentId, selectedModuleId!)"
                                            class="h-8 w-16 rounded-md border border-input bg-background px-2 text-center text-sm read-only:cursor-not-allowed read-only:bg-muted read-only:text-muted-foreground aria-invalid:border-destructive"
                                            @input="clearInvalid(draftKey(student.studentEnrolmentId))"
                                            @keydown="onCellKeydown($event, rowIndex, 0, 1, draftKey(student.studentEnrolmentId), student.moduleMark?.mark != null ? String(student.moduleMark.mark) : '')"
                                            @blur="onModuleMarkBlur(student)"
                                        />
                                        <LoaderCircle
                                            v-if="isSaving(student.studentEnrolmentId, selectedModuleId!)"
                                            class="h-3.5 w-3.5 animate-spin text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <span
                                        v-if="invalidKeys[draftKey(student.studentEnrolmentId)]"
                                        :id="cellErrorId(draftKey(student.studentEnrolmentId))"
                                        class="text-[11px] text-destructive"
                                    >
                                        {{ $t('academic_calendar.course_work_mark_invalid') }}
                                    </span>
                                </div>
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
                    <caption class="sr-only">{{ $t('academic_calendar.course_work_marksheet_caption', { module: selectedModuleLabel }) }}</caption>
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th scope="col" class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                            <th scope="col" class="j-th text-left">{{ $t('academic_calendar.course_work_candidate_number') }}</th>
                            <th scope="col" class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                            <th
                                v-for="type in assessmentTypes"
                                :key="type.id"
                                scope="col"
                                class="j-th text-center text-xs"
                            >
                                {{ type.name }}
                                <span v-if="type.weightPercent" class="block font-normal text-muted-foreground">
                                    ({{ type.weightPercent }}%)
                                </span>
                                <span v-if="windowFor(type.id)" :id="columnStatusId(type.id)" class="block font-normal text-muted-foreground">
                                    {{ windowFor(type.id)?.statusLabel }}
                                </span>
                            </th>
                            <th scope="col" class="j-th text-center">{{ $t('academic_calendar.course_work_total_60') }}</th>
                            <th scope="col" class="j-th text-left">{{ $t('academic_calendar.course_work_remark') }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr v-for="(student, rowIndex) in moduleStudents" :key="student.studentEnrolmentId" class="j-tr">
                            <th scope="row" class="j-td text-left font-normal">{{ student.name }}</th>
                            <td class="j-td">{{ student.candidateNumber ?? '---' }}</td>
                            <td class="j-td font-mono text-xs">{{ student.studentNumber ?? $t('students.not_available') }}</td>
                            <td
                                v-for="(type, columnIndex) in assessmentTypes"
                                :key="`${student.studentEnrolmentId}-${type.id}`"
                                class="j-td text-center"
                            >
                                <div v-if="findAssessment(student, type.id)" class="inline-flex flex-col items-center gap-1">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <input
                                            v-model="draftMarks[draftKey(student.studentEnrolmentId, type.id)]"
                                            type="text"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="3"
                                            autocomplete="off"
                                            :data-course-work-cell="`${rowIndex}-${columnIndex}`"
                                            :aria-label="markLabel(student, type.name)"
                                            :aria-describedby="describedBy(draftKey(student.studentEnrolmentId, type.id), type.id, !canEditAssessment(findAssessment(student, type.id)!))"
                                            :aria-invalid="invalidKeys[draftKey(student.studentEnrolmentId, type.id)] ? 'true' : undefined"
                                            :aria-busy="isSaving(student.studentEnrolmentId, selectedModuleId!, type.id) ? 'true' : undefined"
                                            :readonly="
                                                !canEditAssessment(findAssessment(student, type.id)!) ||
                                                isSaving(student.studentEnrolmentId, selectedModuleId!, type.id)
                                            "
                                            class="h-8 w-16 rounded-md border border-input bg-background px-2 text-center text-sm read-only:cursor-not-allowed read-only:bg-muted read-only:text-muted-foreground aria-invalid:border-destructive"
                                            @input="clearInvalid(draftKey(student.studentEnrolmentId, type.id))"
                                            @keydown="onCellKeydown($event, rowIndex, columnIndex, assessmentTypes.length, draftKey(student.studentEnrolmentId, type.id), findAssessment(student, type.id)?.mark != null ? String(findAssessment(student, type.id)?.mark) : '')"
                                            @blur="onMarkBlur(student, findAssessment(student, type.id)!)"
                                        />
                                        <LoaderCircle
                                            v-if="isSaving(student.studentEnrolmentId, selectedModuleId!, type.id)"
                                            class="h-3.5 w-3.5 animate-spin text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <span
                                        v-if="invalidKeys[draftKey(student.studentEnrolmentId, type.id)]"
                                        :id="cellErrorId(draftKey(student.studentEnrolmentId, type.id))"
                                        class="text-[11px] text-destructive"
                                    >
                                        {{ $t('academic_calendar.course_work_mark_invalid') }}
                                    </span>
                                </div>
                                <span v-else class="sr-only">{{ $t('students.not_available') }}</span>
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
