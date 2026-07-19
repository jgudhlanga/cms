<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, warningAlert } from '@/lib/alerts';
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
    onSaveRow: (mark: number | null, remark: string | null) => Promise<boolean>;
}

const props = defineProps<Props>();

const markInput = ref<number | null>(props.assessment.mark);
const remarkInput = ref<string>(props.assessment.remark ?? '');

watch(
    () => props.assessment,
    (value) => {
        markInput.value = value.mark;
        remarkInput.value = value.remark ?? '';
    },
    { deep: true },
);

const canEdit = computed((): boolean =>
    props.assessment.markId != null ? props.canUpdate : props.canCreate,
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

    if (isCourseWorkMarkInputInvalid(markInput.value)) {
        warningAlert(trans('academic_calendar.course_work_mark_invalid'));

        return;
    }

    const mark = normalizedMark();

    if (mark === null) {
        warningAlert(trans('academic_calendar.course_work_mark_required'));

        return;
    }

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
    <div class="flex flex-col gap-3 rounded-lg border border-border bg-muted/20 px-4 py-3 sm:flex-row sm:items-end">
        <div class="flex-1 space-y-1">
            <label class="text-xs font-bold text-muted-foreground uppercase">
                {{ assessment.assessmentTypeName }}
            </label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <span class="mb-1 block text-[0.7rem] uppercase tracking-wide text-muted-foreground">
                        {{ $t('academic_calendar.course_work_mark') }}
                    </span>
                    <input
                        v-model.number="markInput"
                        type="number"
                        min="0"
                        max="100"
                        step="1"
                        :disabled="!canEdit || saving"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>
                <div>
                    <span class="mb-1 block text-[0.7rem] uppercase tracking-wide text-muted-foreground">
                        {{ $t('academic_calendar.course_work_remark') }}
                    </span>
                    <input
                        v-model="remarkInput"
                        type="text"
                        :disabled="!canEdit || saving"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>
            </div>
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
        </BaseButton>
    </div>
</template>
