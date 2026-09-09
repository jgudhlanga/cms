<script setup lang="ts">
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { TextFieldType } from '@/enums/inputs';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { SelectOption } from '@/types/utils';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

export interface StudentIntakePeriodOption {
    id: number;
    name: string;
}

const props = defineProps<{
    studentId: string | number;
    intakePeriodOptions: StudentIntakePeriodOption[];
    currentIntakePeriodId?: string | number | null;
}>();

const { closeModal, isOpen } = useModalStore();

const selectedIntakePeriodId = ref<string | number | null>(null);

const options = computed<SelectOption[]>(() =>
    props.intakePeriodOptions.map((option) => ({
        value: option.id,
        label: option.name,
    })),
);

const form = useForm({
    intake_period_id: '' as string | number,
    reason: '',
});

watch(
    () => isOpen(APP_MODULE_KEYS.student_intake_period_change),
    (opened) => {
        if (opened) {
            selectedIntakePeriodId.value = props.currentIntakePeriodId ?? null;
            form.reason = '';
            form.clearErrors();
        }
    },
);

watch(selectedIntakePeriodId, () => clearFormErrors(form, 'intake_period_id'));

const onClose = (): void => {
    selectedIntakePeriodId.value = null;
    form.reset();
    form.clearErrors();
};

const save = (): void => {
    form.intake_period_id = selectedIntakePeriodId.value ?? '';

    form.transform((data) => ({
        ...data,
        reason: data.reason.trim(),
    })).patch(route('students.intake-period.update', props.studentId), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal(APP_MODULE_KEYS.student_intake_period_change);
            onClose();
            router.visit(window.location.href, {
                replace: true,
                preserveState: false,
                preserveScroll: false,
            });
        },
    });
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_intake_period_change"
        :title="$t('students.change_intake_period_modal_title')"
        :size="SizeVariant.sm"
        action-btn-text="trans.save"
        cancel-btn-text="trans.cancel"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3">
                <p class="text-xs text-muted-foreground">
                    {{ $t('students.change_intake_period_help') }}
                </p>
                <BaseSelect
                    v-model="selectedIntakePeriodId"
                    :label="$t('students.change_intake_period_field_label')"
                    :placeholder="$t('students.change_intake_period_field_placeholder')"
                    :options="options"
                    :is-required="true"
                    :error="form.errors.intake_period_id"
                />
                <BaseInput
                    input-id="student_intake_period_reason"
                    v-model="form.reason"
                    :type="TextFieldType.textarea"
                    rows="3"
                    :label="$t('students.change_intake_period_reason_label')"
                    :placeholder="$t('students.change_intake_period_reason_placeholder')"
                    :is-required="true"
                    :error="form.errors.reason"
                    @input="clearFormErrors(form, 'reason')"
                />
            </div>
        </template>
    </BaseModal>
</template>
