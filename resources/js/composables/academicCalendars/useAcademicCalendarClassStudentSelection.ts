import type { AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useAcademicCalendarClassStudentSelection(sortedStudents: ComputedRef<AcademicCalendarClassPreviewStudent[]>): {
    selectedStudentEnrolmentIds: Ref<number[]>;
    selectAllChangeClassModel: ComputedRef<boolean>;
    toggleSelectAllChangeClassFromRow: () => void;
    onSelectAllRowKeydown: (event: KeyboardEvent) => void;
} {
    const selectedStudentEnrolmentIds = ref<number[]>([]);

    const selectAllChangeClassModel = computed({
        get() {
            const list = sortedStudents.value;
            if (list.length === 0) {
                return false;
            }

            return selectedStudentEnrolmentIds.value.length === list.length;
        },
        set(checked: boolean) {
            if (checked) {
                selectedStudentEnrolmentIds.value = sortedStudents.value.map((s) => s.studentEnrolmentId);
            } else {
                selectedStudentEnrolmentIds.value = [];
            }
        },
    });

    const toggleSelectAllChangeClassFromRow = (): void => {
        const list = sortedStudents.value;
        if (list.length === 0) {
            return;
        }
        if (selectedStudentEnrolmentIds.value.length === list.length) {
            selectedStudentEnrolmentIds.value = [];
        } else {
            selectedStudentEnrolmentIds.value = list.map((s) => s.studentEnrolmentId);
        }
    };

    const onSelectAllRowKeydown = (event: KeyboardEvent): void => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleSelectAllChangeClassFromRow();
        }
    };

    return {
        selectedStudentEnrolmentIds,
        selectAllChangeClassModel,
        toggleSelectAllChangeClassFromRow,
        onSelectAllRowKeydown,
    };
}
