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

export interface StudentStatusOption {
    slug: string;
    name: string;
    description?: string;
}

const props = defineProps<{
    studentId: string | number;
    statusOptions: StudentStatusOption[];
    currentStatus?: string;
}>();

const { closeModal, isOpen } = useModalStore();

const selectedStatus = ref<string | null>(null);

const options = computed<SelectOption[]>(() =>
    props.statusOptions.map((option) => ({
        value: option.slug,
        label: option.name,
    })),
);

const form = useForm({
    status: '',
    reason: '',
});

watch(
    () => isOpen(APP_MODULE_KEYS.student_status_change),
    (opened) => {
        if (opened) {
            const current = props.statusOptions.find((option) => option.name === props.currentStatus);
            selectedStatus.value = current?.slug ?? null;
            form.reason = '';
            form.clearErrors();
        }
    },
);

watch(selectedStatus, () => clearFormErrors(form, 'status'));

const onClose = (): void => {
    selectedStatus.value = null;
    form.reset();
    form.clearErrors();
};

const save = (): void => {
    form.status = selectedStatus.value ?? '';

    form.transform((data) => ({
        ...data,
        reason: data.reason.trim(),
    })).patch(route('students.status.update', props.studentId), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal(APP_MODULE_KEYS.student_status_change);
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
        :name="APP_MODULE_KEYS.student_status_change"
        :title="$t('students.change_status_modal_title')"
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
                    {{ $t('students.change_status_help') }}
                </p>
                <BaseSelect
                    v-model="selectedStatus"
                    :label="$t('students.change_status_field_label')"
                    :placeholder="$t('students.change_status_field_placeholder')"
                    :options="options"
                    :is-required="true"
                    :error="form.errors.status"
                />
                <BaseInput
                    input-id="student_status_reason"
                    v-model="form.reason"
                    :type="TextFieldType.textarea"
                    rows="3"
                    :label="$t('students.change_status_reason_label')"
                    :placeholder="$t('students.change_status_reason_placeholder')"
                    :is-required="true"
                    :error="form.errors.reason"
                    @input="clearFormErrors(form, 'reason')"
                />
            </div>
        </template>
    </BaseModal>
</template>
