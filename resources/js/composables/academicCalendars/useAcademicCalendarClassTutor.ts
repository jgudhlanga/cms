import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';

export type AssignClassTutorModalParams = {
    academicCalendarClassId: number;
    staffId?: number | null;
};

export function openAssignClassTutorModal(params: AssignClassTutorModalParams): void {
    const { openModal } = useModalStore();
    openModal(APP_MODULE_KEYS.assign_class_tutor, params);
}
