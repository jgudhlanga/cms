import type { VerifiedStudentForFinalEnrolment } from '@/types/verified-students-final-enrolment';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useVerifiedStudentsFinalEnrolmentSelection(
    students: ComputedRef<VerifiedStudentForFinalEnrolment[]>,
): {
    selectedStudentApplicationIds: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    selectedCount: ComputedRef<number>;
    clearSelection: () => void;
    pruneSelectionToVisibleStudents: () => void;
} {
    const selectedStudentApplicationIds = ref<number[]>([]);

    const selectAllModel = computed({
        get() {
            const list = students.value;
            if (list.length === 0) {
                return false;
            }

            const selectedSet = new Set(selectedStudentApplicationIds.value);

            return list.every((student) => selectedSet.has(student.id));
        },
        set(checked: boolean) {
            if (checked) {
                selectedStudentApplicationIds.value = students.value.map((student) => student.id);
            } else {
                selectedStudentApplicationIds.value = [];
            }
        },
    });

    const selectedCount = computed(() => selectedStudentApplicationIds.value.length);

    const clearSelection = (): void => {
        selectedStudentApplicationIds.value = [];
    };

    const pruneSelectionToVisibleStudents = (): void => {
        const visibleIds = new Set(students.value.map((student) => student.id));
        selectedStudentApplicationIds.value = selectedStudentApplicationIds.value.filter((id) => visibleIds.has(id));
    };

    return {
        selectedStudentApplicationIds,
        selectAllModel,
        selectedCount,
        clearSelection,
        pruneSelectionToVisibleStudents,
    };
}
