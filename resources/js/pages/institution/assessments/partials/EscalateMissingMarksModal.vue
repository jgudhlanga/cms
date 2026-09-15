<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { formatIsoDate } from '@/lib/departmentAssessmentCalendars';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { MissingMarksEscalationTarget } from '@/types/assessments';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const target = ref<MissingMarksEscalationTarget | null>(null);

const form = useForm({
    assessment_calendar_id: 0,
    notes: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    target.value = getModalEdit(APP_MODULE_KEYS.missing_marks_escalate) ?? null;
    form.assessment_calendar_id = target.value?.assessmentCalendarId ?? 0;
    form.notes = '';
    form.defaults();
    form.clearErrors();
});

// "Already escalated" and "nothing to escalate" come back under `escalation`, which is not a form field.
const escalationError = computed((): string | undefined => (form.errors as Record<string, string | undefined>).escalation);

const save = () => {
    form.post(
        route('missing-marks-report.escalate'),
        buildFormOptions(
            form,
            trans('assessments.missing_marks_escalated'),
            trans('trans.item_save_failure', { item: trans('assessments.missing_marks_escalate') }),
            APP_MODULE_KEYS.missing_marks_escalate,
        ),
    );
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.missing_marks_escalate"
        :title="$t('assessments.missing_marks_escalate_title')"
        :on-form-action="() => save()"
        :form="form"
        :size="SizeVariant.md"
    >
        <template #body>
            <div v-if="target" class="space-y-3">
                <p class="text-sm text-foreground">
                    {{
                        $t('assessments.missing_marks_escalate_scope', {
                            assessment: target.assessmentTypeName,
                            date: target.dueDate ? formatIsoDate(target.dueDate) : '—',
                        })
                    }}
                </p>

                <p v-if="escalationError" role="alert" class="text-sm text-destructive">{{ escalationError }}</p>

                <div class="space-y-1">
                    <label for="missing_marks_escalation_notes" class="text-sm font-medium text-foreground">
                        {{ $t('assessments.missing_marks_notes') }}
                    </label>
                    <textarea
                        id="missing_marks_escalation_notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="2000"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :aria-invalid="form.errors.notes ? 'true' : undefined"
                        aria-describedby="missing_marks_escalation_notes_hint missing_marks_escalation_notes_error"
                        @input="clearFormErrors(form, 'notes')"
                    />
                    <p id="missing_marks_escalation_notes_hint" class="text-xs text-muted-foreground">
                        {{ $t('assessments.missing_marks_notes_hint') }}
                    </p>
                    <p v-if="form.errors.notes" id="missing_marks_escalation_notes_error" role="alert" class="text-sm text-destructive">
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
