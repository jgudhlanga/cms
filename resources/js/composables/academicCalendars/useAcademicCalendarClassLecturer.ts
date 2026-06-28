import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';

export type AssignClassLecturerModalParams = {
    academicCalendarClassId: number;
    staffId?: number | null;
};

export function openAssignClassLecturerModal(params: AssignClassLecturerModalParams): void {
    const { openModal } = useModalStore();
    openModal(APP_MODULE_KEYS.assign_class_lecturer, params);
}
