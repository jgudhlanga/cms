import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { useModalStore } from '@/store/core/useModalStore';
import type { AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import type { ComputedRef } from 'vue';

export const ADD_STUDENTS_MODAL = 'academic_calendar_add_students';

export function useAcademicCalendarClassAddStudents(addStudentsUrl: ComputedRef<string>) {
    const { openModal, closeModal } = useModalStore();

    const addStudentsForm = useForm({
        student_enrolment_ids: [] as number[],
    });

    const openAddStudentsModal = (): void => {
        addStudentsForm.student_enrolment_ids = [];
        addStudentsForm.clearErrors();
        openModal(ADD_STUDENTS_MODAL);
    };

    const submitAddStudents = (): void => {
        addStudentsForm.post(addStudentsUrl.value, {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans('academic_calendar.add_students_success', { count: addStudentsForm.student_enrolment_ids.length }));
                closeModal(ADD_STUDENTS_MODAL);
                addStudentsForm.student_enrolment_ids = [];
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.add_students_failed')));
            },
        });
    };

    const resetAddStudentsFormOnModalClose = (): void => {
        addStudentsForm.clearErrors();
        addStudentsForm.student_enrolment_ids = [];
    };

    const isStudentSelected = (student: AcademicCalendarClassPreviewStudent): boolean =>
        addStudentsForm.student_enrolment_ids.includes(student.studentEnrolmentId);

    return {
        addStudentsForm,
        openAddStudentsModal,
        submitAddStudents,
        resetAddStudentsFormOnModalClose,
        isStudentSelected,
    };
}
