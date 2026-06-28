<script setup lang="ts">
import StaffSelect from '@/components/core/form/select/StaffSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { useModalStore } from '@/store/core/useModalStore';
import type { AssignClassLecturerModalParams } from '@/composables/academicCalendars/useAcademicCalendarClassLecturer';
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
    const edit = modalStore.getEdit(APP_MODULE_KEYS.assign_class_lecturer) as AssignClassLecturerModalParams | undefined;
    const classId = edit?.academicCalendarClassId;
    return classId != null && classId > 0 ? classId : null;
});

const assignLecturerUrl = computed(() => {
    if (activeClassId.value == null) {
        return '';
    }

    return route('academic-calendars.department-classes.assign-lecturer', {
        institution_department: String(props.institutionDepartmentId),
        calendar_year: props.calendarYear,
        academic_calendar_class: String(activeClassId.value),
    });
});

const staffSelectUrl = computed(
    () => `${route('v1.department-metadata.staff', String(props.institutionDepartmentId))}?page_size=all`,
);

const form = useForm({
    staff_id: null as number | null,
});

watch(
    () => modalStore.isOpen(APP_MODULE_KEYS.assign_class_lecturer),
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        const edit = modalStore.getEdit(APP_MODULE_KEYS.assign_class_lecturer) as AssignClassLecturerModalParams | undefined;
        form.staff_id = edit?.staffId ?? null;
        form.clearErrors();
    },
);

const submitAssignLecturer = (): void => {
    if (assignLecturerUrl.value === '') {
        return;
    }

    form.patch(assignLecturerUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            successAlert(trans('academic_calendar.lecturer_assigned_success'));
            modalStore.closeModal(APP_MODULE_KEYS.assign_class_lecturer);
        },
        onError: (errors) => {
            errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.lecturer_assign_failed')));
        },
    });
};
</script>

<template>
    <BaseModal
        :title="$t('academic_calendar.assign_lecturer_modal_title')"
        :name="APP_MODULE_KEYS.assign_class_lecturer"
        :form="form"
        :on-form-action="submitAssignLecturer"
        :size="SizeVariant.sm"
        cancel-btn-text="trans.close"
        action-btn-text="trans.save"
    >
        <template #body>
            <StaffSelect
                v-model="form.staff_id"
                :url="staffSelectUrl"
                :label="$tChoice('trans.staff', 1)"
                :placeholder="$t('academic_calendar.assign_lecturer')"
                :error="form.errors.staff_id"
                :is-clearable="true"
            />
        </template>
    </BaseModal>
</template>
