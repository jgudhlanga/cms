import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { useModalStore } from '@/store/core/useModalStore';
import type { AcademicCalendarClassDetail } from '@/types/academic-calendar';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';

export const EDIT_CLASS_MODAL = 'academic_calendar_edit_class';

export function useAcademicCalendarClassEdit(
    updateClassUrl: ComputedRef<string>,
    academicCalendarClass: Ref<AcademicCalendarClassDetail>,
) {
    const { openModal, closeModal } = useModalStore();

    const editClassForm = useForm({
        name: '',
        description: '' as string | null,
    });

    const openEditClassModal = (): void => {
        editClassForm.name = academicCalendarClass.value.name;
        editClassForm.description = academicCalendarClass.value.description ?? '';
        editClassForm.clearErrors();
        openModal(EDIT_CLASS_MODAL);
    };

    const submitEditClass = (): void => {
        editClassForm.patch(updateClassUrl.value, {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans('academic_calendar.update_class_success'));
                closeModal(EDIT_CLASS_MODAL);
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.update_class_failed')));
            },
        });
    };

    const resetEditClassFormOnModalClose = (): void => {
        editClassForm.clearErrors();
    };

    return {
        editClassForm,
        openEditClassModal,
        submitEditClass,
        resetEditClassFormOnModalClose,
    };
}
