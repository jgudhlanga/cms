import type { AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useAcademicCalendarClassStudentSelection(
    sortedStudents: ComputedRef<AcademicCalendarClassPreviewStudent[]>,
): {
    selectedStudentProgramIds: Ref<number[]>;
    selectAllChangeClassModel: ComputedRef<boolean>;
    toggleSelectAllChangeClassFromRow: () => void;
    onSelectAllRowKeydown: (event: KeyboardEvent) => void;
} {
    const selectedStudentProgramIds = ref<number[]>([]);

    const selectAllChangeClassModel = computed({
        get() {
            const list = sortedStudents.value;
            if (list.length === 0) {
                return false;
            }

            return selectedStudentProgramIds.value.length === list.length;
        },
        set(checked: boolean) {
            if (checked) {
                selectedStudentProgramIds.value = sortedStudents.value.map((s) => s.studentProgramId);
            } else {
                selectedStudentProgramIds.value = [];
            }
        },
    });

    const toggleSelectAllChangeClassFromRow = (): void => {
        const list = sortedStudents.value;
        if (list.length === 0) {
            return;
        }
        if (selectedStudentProgramIds.value.length === list.length) {
            selectedStudentProgramIds.value = [];
        } else {
            selectedStudentProgramIds.value = list.map((s) => s.studentProgramId);
        }
    };

    const onSelectAllRowKeydown = (event: KeyboardEvent): void => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleSelectAllChangeClassFromRow();
        }
    };

    return {
        selectedStudentProgramIds,
        selectAllChangeClassModel,
        toggleSelectAllChangeClassFromRow,
        onSelectAllRowKeydown,
    };
}
