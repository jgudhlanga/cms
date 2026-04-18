import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { useModalStore } from '@/store/core/useModalStore';
import type { AcademicCalendarClassMoveTarget } from '@/types/academic-calendar';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';

export const MOVE_STUDENTS_MODAL = 'academic_calendar_move_students';

export function useAcademicCalendarClassMoveStudents(
    moveStudentsUrl: ComputedRef<string>,
    moveTargetClasses: Ref<AcademicCalendarClassMoveTarget[]>,
    selectedStudentEnrolmentIds: Ref<number[]>,
) {
    const { openModal, closeModal } = useModalStore();

    const moveForm = useForm({
        student_enrolment_ids: [] as number[],
        target_academic_calendar_class_id: null as number | null,
    });

    const openMoveStudentsModal = (): void => {
        if (moveTargetClasses.value.length === 0) {
            return;
        }
        moveForm.student_enrolment_ids = [...selectedStudentEnrolmentIds.value];
        moveForm.target_academic_calendar_class_id = moveTargetClasses.value[0]?.id ?? null;
        moveForm.clearErrors();
        openModal(MOVE_STUDENTS_MODAL);
    };

    const submitMoveStudents = (): void => {
        moveForm.post(moveStudentsUrl.value, {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans('academic_calendar.move_students_success'));
                closeModal(MOVE_STUDENTS_MODAL);
                selectedStudentEnrolmentIds.value = [];
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.move_students_failed')));
            },
        });
    };

    const resetMoveFormOnModalClose = (): void => {
        moveForm.clearErrors();
    };

    return {
        moveForm,
        openMoveStudentsModal,
        submitMoveStudents,
        resetMoveFormOnModalClose,
    };
}
