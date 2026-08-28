<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { TextFieldType } from '@/enums/inputs';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    studentId: string | number;
    studentNumber?: string;
}>();

const { closeModal, isOpen } = useModalStore();

const form = useForm({
    student_number: props.studentNumber ?? '',
    reason: '',
});

watch(
    () => isOpen(APP_MODULE_KEYS.student_number_change),
    (opened) => {
        if (opened) {
            form.student_number = props.studentNumber ?? '';
            form.reason = '';
            form.clearErrors();
        }
    },
);

const onClose = (): void => {
    form.reset();
    form.clearErrors();
};

const save = (): void => {
    form.transform((data) => ({
        ...data,
        student_number: data.student_number.trim(),
        reason: data.reason.trim(),
    })).patch(route('students.student-number.update', props.studentId), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal(APP_MODULE_KEYS.student_number_change);
            onClose();
        },
    });
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_number_change"
        :title="$t('students.change_student_number_modal_title')"
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
                    {{ $t('students.change_student_number_help') }}
                </p>
                <BaseInput
                    input-id="student_number"
                    v-model="form.student_number"
                    :label="$t('students.change_student_number_field_label')"
                    :is-required="true"
                    :error="form.errors.student_number"
                    @input="clearFormErrors(form, 'student_number')"
                />
                <BaseInput
                    input-id="student_number_reason"
                    v-model="form.reason"
                    :type="TextFieldType.textarea"
                    rows="3"
                    :label="$t('students.change_student_number_reason_label')"
                    :placeholder="$t('students.change_student_number_reason_placeholder')"
                    :is-required="true"
                    :error="form.errors.reason"
                    @input="clearFormErrors(form, 'reason')"
                />
            </div>
        </template>
    </BaseModal>
</template>
