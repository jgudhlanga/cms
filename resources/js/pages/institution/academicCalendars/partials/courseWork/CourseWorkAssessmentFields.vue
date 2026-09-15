<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import { isCourseWorkMarkInputInvalid, parseCourseWorkMark } from '@/lib/course-work';
import type { CourseWorkAssessment } from '@/types/course-work';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const { open: openConfirmDialog } = useCustomConfirmDialog();

interface Props {
    assessment: CourseWorkAssessment;
    canCreate: boolean;
    canUpdate: boolean;
    saving: boolean;
    readOnly?: boolean;
    readOnlyMessage?: string | null;
    onSaveRow: (mark: number | null, remark: string | null) => Promise<boolean>;
}

const props = defineProps<Props>();

let instanceCounter = 0;
const fieldId = `course-work-field-${props.assessment.assessmentTypeId ?? 'module'}-${props.assessment.markId ?? 'new'}-${++instanceCounter}-${Math.random().toString(36).slice(2, 7)}`;
const ids = {
    heading: `${fieldId}-heading`,
    mark: `${fieldId}-mark`,
    remark: `${fieldId}-remark`,
    readOnly: `${fieldId}-read-only`,
    error: `${fieldId}-error`,
};

const markInput = ref<number | string | null>(props.assessment.mark);
const remarkInput = ref<string>(props.assessment.remark ?? '');
const fieldError = ref<string | null>(null);

watch(
    () => props.assessment,
    (value) => {
        markInput.value = value.mark;
        remarkInput.value = value.remark ?? '';
        fieldError.value = null;
    },
    { deep: true },
);

const canEdit = computed((): boolean =>
    !props.readOnly && (props.assessment.markId != null ? props.canUpdate : props.canCreate),
);

const isLocked = computed((): boolean => !canEdit.value || props.saving);

const describedBy = computed((): string | undefined =>
    [fieldError.value ? ids.error : null, props.readOnly && props.readOnlyMessage ? ids.readOnly : null].filter(Boolean).join(' ') || undefined,
);

const normalizedMark = (): number | null => parseCourseWorkMark(markInput.value);

const buildConfirmMessage = (mark: number | null, remark: string | null): string => {
    const markLabel =
        mark !== null ? String(mark) : trans('students.not_available');

    let message = trans('academic_calendar.course_work_save_confirm_message', {
        assessment: props.assessment.assessmentTypeName,
        mark: markLabel,
    });

    if (remark) {
        message += trans('academic_calendar.course_work_save_confirm_message_remark', { remark });
    }

    return message;
};

const onSave = async (): Promise<void> => {
    if (props.saving) {
        return;
    }

    const remark = remarkInput.value.trim() || null;

    // Validation stays next to the field (and is announced) rather than in a disappearing toast.
    if (isCourseWorkMarkInputInvalid(markInput.value)) {
        fieldError.value = trans('academic_calendar.course_work_mark_invalid');

        return;
    }

    const mark = normalizedMark();

    if (mark === null) {
        fieldError.value = trans('academic_calendar.course_work_mark_required');

        return;
    }

    fieldError.value = null;
    const isUpdate = props.assessment.markId != null;

    const confirmed = await openConfirmDialog({
        title: trans(
            isUpdate
                ? 'academic_calendar.course_work_update_confirm_title'
                : 'academic_calendar.course_work_save_confirm_title',
        ),
        message: buildConfirmMessage(mark, remark),
        note: trans('academic_calendar.course_work_save_confirm_note'),
        confirmText: trans('academic_calendar.course_work_save'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    try {
        await props.onSaveRow(mark, remark);
    } catch {
        errorAlert(trans('academic_calendar.course_work_save_failed'));
    }
};
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-lg border border-border bg-muted/20 px-4 py-3 sm:flex-row sm:items-end"
        role="group"
        :aria-labelledby="ids.heading"
    >
        <div class="flex-1 space-y-1">
            <p :id="ids.heading" class="text-xs font-bold text-muted-foreground uppercase">
                {{ assessment.assessmentTypeName }}
            </p>
            <p v-if="readOnly && readOnlyMessage" :id="ids.readOnly" class="text-xs text-muted-foreground">
                {{ readOnlyMessage }}
            </p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label :for="ids.mark" class="mb-1 block text-[0.7rem] uppercase tracking-wide text-muted-foreground">
                        {{ $t('academic_calendar.course_work_mark') }}
                    </label>
                    <input
                        :id="ids.mark"
                        v-model="markInput"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="3"
                        autocomplete="off"
                        :readonly="isLocked"
                        :aria-readonly="isLocked ? 'true' : undefined"
                        :aria-invalid="fieldError ? 'true' : undefined"
                        :aria-describedby="describedBy"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring read-only:cursor-not-allowed read-only:bg-muted read-only:text-muted-foreground aria-invalid:border-destructive"
                        @input="fieldError = null"
                    />
                </div>
                <div>
                    <label :for="ids.remark" class="mb-1 block text-[0.7rem] uppercase tracking-wide text-muted-foreground">
                        {{ $t('academic_calendar.course_work_remark') }}
                    </label>
                    <input
                        :id="ids.remark"
                        v-model="remarkInput"
                        type="text"
                        maxlength="2000"
                        :readonly="isLocked"
                        :aria-readonly="isLocked ? 'true' : undefined"
                        :aria-describedby="readOnly && readOnlyMessage ? ids.readOnly : undefined"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring read-only:cursor-not-allowed read-only:bg-muted read-only:text-muted-foreground"
                    />
                </div>
            </div>
            <p v-if="fieldError" :id="ids.error" role="alert" class="text-sm text-destructive">{{ fieldError }}</p>
        </div>
        <BaseButton
            v-if="canEdit"
            type="button"
            :variant="ColorVariant.primary"
            :size="ButtonSize.xs"
            classes="rounded-full min-w-22 shrink-0"
            :processing="saving"
            :disabled="saving"
            :aria-busy="saving"
            @click="onSave"
        >
            {{ $t('academic_calendar.course_work_save') }}
            <span class="sr-only">: {{ assessment.assessmentTypeName }}</span>
        </BaseButton>
    </div>
</template>
