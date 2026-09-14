<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { formatIsoDate } from '@/lib/departmentAssessmentCalendars';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { CourseWorkExtensionDecisionTarget } from '@/types/course-work-extensions';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const target = ref<CourseWorkExtensionDecisionTarget | null>(null);

const form = useForm({
    approved_until: '',
    note: '',
});

const tomorrow = (): string => {
    const date = new Date();
    date.setDate(date.getDate() + 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

const isApprove = computed(() => target.value?.mode === 'approve');
const maxApprovalDate = computed(() => {
    const extension = target.value?.extension;

    return extension && !extension.can.approveBeyondGlobal ? (extension.globalEndDate ?? undefined) : undefined;
});

const { modals } = useModalStore();

watch(modals!, () => {
    target.value = getModalEdit(APP_MODULE_KEYS.course_work_extension_decision) ?? null;
    form.approved_until = target.value?.mode === 'approve' ? (target.value.extension.requestedUntil ?? '') : '';
    form.note = '';
    form.defaults();
    form.clearErrors();
});

const title = computed(() => {
    switch (target.value?.mode) {
        case 'approve':
            return trans('academic_calendar.course_work_extension_approve_action');
        case 'revoke':
            return trans('academic_calendar.course_work_extension_revoke_action');
        default:
            return trans('academic_calendar.course_work_extension_reject_action');
    }
});

const save = () => {
    if (!target.value) {
        return;
    }

    const routeName = {
        approve: 'course-work-extensions.approve',
        reject: 'course-work-extensions.reject',
        revoke: 'course-work-extensions.revoke',
    }[target.value.mode];

    form.post(
        route(routeName, { course_work_capture_extension: target.value.extension.id }),
        buildFormOptions(
            form,
            title.value,
            trans('trans.item_save_failure', { item: trans('academic_calendar.course_work_extensions_title') }),
            APP_MODULE_KEYS.course_work_extension_decision,
        ),
    );
};

// Status conflicts (already decided) are reported by the service under `status`, which is not a form field.
const statusError = computed((): string | undefined => (form.errors as Record<string, string | undefined>).status);
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.course_work_extension_decision"
        :title="title"
        :on-form-action="() => save()"
        :form="form"
        :size="SizeVariant.md"
    >
        <template #body>
            <div v-if="target" class="space-y-3">
                <div class="space-y-1 text-sm">
                    <p class="font-medium text-foreground">
                        {{ target.extension.assessmentName }} · {{ target.extension.moduleName }}
                    </p>
                    <p class="text-muted-foreground">{{ target.extension.className }} · {{ target.extension.requesterName }}</p>
                    <p class="text-muted-foreground">{{ target.extension.reason }}</p>
                </div>

                <p v-if="statusError" role="alert" class="text-sm text-destructive">{{ statusError }}</p>

                <template v-if="isApprove">
                    <BaseDatePicker
                        input-id="extension_approved_until"
                        :label="$t('academic_calendar.course_work_extension_until_label')"
                        :enable-time-picker="false"
                        v-model="form.approved_until"
                        :is-required="true"
                        :teleport="true"
                        :min-date="tomorrow()"
                        :max-date="maxApprovalDate"
                        :error="form.errors.approved_until"
                        aria-describedby="extension_deadline_hint"
                        @update:model-value="clearFormErrors(form, 'approved_until')"
                    />
                    <p v-if="target.extension.globalEndDate" id="extension_deadline_hint" class="text-xs text-muted-foreground">
                        {{
                            target.extension.can.approveBeyondGlobal
                                ? $t('academic_calendar.course_work_extension_college_deadline', {
                                      date: formatIsoDate(target.extension.globalEndDate),
                                  })
                                : $t('academic_calendar.course_work_extension_beyond_global_hint', {
                                      date: formatIsoDate(target.extension.globalEndDate),
                                  })
                        }}
                    </p>
                </template>

                <div class="space-y-1">
                    <label for="extension_decision_note" class="text-sm font-medium text-foreground">
                        {{ $t('academic_calendar.course_work_extension_note_label') }}
                    </label>
                    <textarea
                        id="extension_decision_note"
                        v-model="form.note"
                        rows="3"
                        maxlength="1000"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :aria-invalid="form.errors.note ? 'true' : undefined"
                        aria-describedby="extension_decision_note_error"
                        @input="clearFormErrors(form, 'note')"
                    />
                    <p v-if="form.errors.note" id="extension_decision_note_error" role="alert" class="text-sm text-destructive">
                        {{ form.errors.note }}
                    </p>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
