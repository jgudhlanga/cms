<script setup lang="ts">
import SelectTutorSelect from '@/components/core/form/select/SelectTutorSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { errorAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { useModalStore } from '@/store/core/useModalStore';
import type { AssignClassTutorModalParams } from '@/composables/academicCalendars/useAcademicCalendarClassTutor';
import { SizeVariant } from '@/enums/sizes';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

const props = defineProps<{
    institutionDepartmentId: number;
    calendarYear: string;
}>();

const modalStore = useModalStore();

const activeClassId = computed((): number | null => {
    const edit = modalStore.getEdit(APP_MODULE_KEYS.assign_class_tutor) as AssignClassTutorModalParams | undefined;
    const classId = edit?.academicCalendarClassId;
    return classId != null && classId > 0 ? classId : null;
});

const assignTutorUrl = computed(() => {
    if (activeClassId.value == null) {
        return '';
    }

    return route('academic-calendars.department-classes.assign-tutor', {
        institution_department: String(props.institutionDepartmentId),
        calendar_year: props.calendarYear,
        academic_calendar_class: String(activeClassId.value),
    });
});

const form = useForm({
    staff_id: null as number | null,
});

watch(
    () => modalStore.isOpen(APP_MODULE_KEYS.assign_class_tutor),
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        const edit = modalStore.getEdit(APP_MODULE_KEYS.assign_class_tutor) as AssignClassTutorModalParams | undefined;
        form.staff_id = edit?.staffId ?? null;
        form.clearErrors();
    },
);

const submitAssignTutor = (): void => {
    if (assignTutorUrl.value === '') {
        return;
    }

    form.patch(assignTutorUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            modalStore.closeModal(APP_MODULE_KEYS.assign_class_tutor);
        },
        onError: (errors) => {
            errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.tutor_assign_failed')));
        },
    });
};
</script>

<template>
    <BaseModal
        :title="$t('academic_calendar.assign_tutor_modal_title')"
        :name="APP_MODULE_KEYS.assign_class_tutor"
        :form="form"
        :on-form-action="submitAssignTutor"
        :size="SizeVariant.sm"
        cancel-btn-text="trans.close"
        action-btn-text="trans.save"
    >
        <template #body>
            <SelectTutorSelect
                v-model="form.staff_id"
                :institution-department-id="institutionDepartmentId"
                :error="form.errors.staff_id"
            />
        </template>
    </BaseModal>
</template>
