<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { CourseWorkExtensionRequestTarget } from '@/types/course-work-extensions';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const target = ref<CourseWorkExtensionRequestTarget | null>(null);

const form = useForm({
    assessment_type_id: '' as number | string,
    requested_until: '',
    reason: '',
});

const localDate = (offsetDays: number): string => {
    const date = new Date();
    date.setDate(date.getDate() + offsetDays);
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
};

const minDate = computed(() => localDate(1));
const maxDate = computed(() => localDate(target.value?.maxDays ?? 14));
const hasSeveralClosedWindows = computed(() => (target.value?.closedWindows.length ?? 0) > 1);

const { modals } = useModalStore();

watch(modals!, () => {
    target.value = getModalEdit(APP_MODULE_KEYS.course_work_extension_request) ?? null;
    const firstWindow = target.value?.closedWindows[0];

    form.assessment_type_id = firstWindow?.assessmentTypeId ?? '';
    form.requested_until = '';
    form.reason = '';
    form.defaults();
    form.clearErrors();
});

const save = () => {
    if (!target.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        assessment_type_id: data.assessment_type_id === '' ? null : data.assessment_type_id,
    })).post(
        route('teaching.classes.extensions.store', {
            academic_calendar_class: target.value.classId,
            course_syllabus_module: target.value.moduleId,
        }),
        buildFormOptions(
            form,
            trans('academic_calendar.course_work_extension_requested'),
            trans('trans.item_save_failure', { item: trans('academic_calendar.course_work_extension_request_action') }),
            APP_MODULE_KEYS.course_work_extension_request,
        ),
    );
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.course_work_extension_request"
        :title="$t('academic_calendar.course_work_extension_request_title')"
        :on-form-action="() => save()"
        :form="form"
        :size="SizeVariant.lg"
    >
        <template #body>
            <div v-if="target" class="space-y-3">
                <p class="text-sm text-foreground">
                    <span class="font-medium">{{ target.moduleName }}</span>
                    <span class="text-muted-foreground"> · {{ target.className }}</span>
                </p>

                <div v-if="hasSeveralClosedWindows" class="space-y-1">
                    <label for="extension_assessment_type_id" class="text-sm font-medium text-foreground">
                        {{ $tChoice('trans.assessment_type', 1) }}
                    </label>
                    <select
                        id="extension_assessment_type_id"
                        v-model="form.assessment_type_id"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :aria-invalid="form.errors.assessment_type_id ? 'true' : undefined"
                        :aria-describedby="form.errors.assessment_type_id ? 'extension_assessment_type_id_error' : undefined"
                        @change="clearFormErrors(form, 'assessment_type_id')"
                    >
                        <option
                            v-for="window in target.closedWindows"
                            :key="window.assessmentTypeId ?? 'module-mark'"
                            :value="window.assessmentTypeId ?? ''"
                        >
                            {{ window.assessmentTypeName }}
                        </option>
                    </select>
                </div>
                <p v-else-if="target.closedWindows[0]" class="text-sm text-muted-foreground">
                    {{ target.closedWindows[0].message }}
                </p>
                <p
                    v-if="form.errors.assessment_type_id"
                    id="extension_assessment_type_id_error"
                    role="alert"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.assessment_type_id }}
                </p>

                <BaseDatePicker
                    input-id="extension_requested_until"
                    :label="$t('academic_calendar.course_work_extension_until_label')"
                    :enable-time-picker="false"
                    v-model="form.requested_until"
                    :is-required="true"
                    :teleport="true"
                    :min-date="minDate"
                    :max-date="maxDate"
                    :error="form.errors.requested_until"
                    @update:model-value="clearFormErrors(form, 'requested_until')"
                />

                <div class="space-y-1">
                    <label for="extension_reason" class="text-sm font-medium text-foreground">
                        {{ $t('academic_calendar.course_work_extension_reason_label') }}
                        <span aria-hidden="true" class="text-destructive">*</span>
                    </label>
                    <textarea
                        id="extension_reason"
                        v-model="form.reason"
                        rows="4"
                        required
                        minlength="20"
                        maxlength="2000"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :aria-invalid="form.errors.reason ? 'true' : undefined"
                        aria-describedby="extension_reason_hint extension_reason_error"
                        @input="clearFormErrors(form, 'reason')"
                    />
                    <p id="extension_reason_hint" class="text-xs text-muted-foreground">
                        {{ $t('academic_calendar.course_work_extension_reason_hint') }}
                    </p>
                    <p v-if="form.errors.reason" id="extension_reason_error" role="alert" class="text-sm text-destructive">
                        {{ form.errors.reason }}
                    </p>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
