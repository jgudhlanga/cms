import { openAssignClassTutorModal } from '@/composables/academicCalendars/useAcademicCalendarClassTutor';

export type AssignClassLecturerModalParams = {
    academicCalendarClassId: number;
    staffId?: number | null;
};

/** @deprecated Use openAssignClassTutorModal */
export function openAssignClassLecturerModal(params: AssignClassLecturerModalParams): void {
    openAssignClassTutorModal(params);
}

export { openAssignClassTutorModal, type AssignClassTutorModalParams } from '@/composables/academicCalendars/useAcademicCalendarClassTutor';
