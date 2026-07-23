import type { FaultyStudentIdNumber } from '@/types/faulty-student-ids';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export function useFaultyStudentIdSelection(
    students: ComputedRef<FaultyStudentIdNumber[]>,
    isSelectable: (student: FaultyStudentIdNumber) => boolean = (student) =>
        student.attributes.rectificationStatus === 'ready_to_fix',
): {
    selectedStudentIds: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    selectedCount: ComputedRef<number>;
    clearSelection: () => void;
    pruneSelectionToVisibleStudents: () => void;
} {
    const selectedStudentIds = ref<number[]>([]);

    const selectableStudents = computed(() => students.value.filter(isSelectable));

    const selectAllModel = computed({
        get() {
            const list = selectableStudents.value;
            if (list.length === 0) {
                return false;
            }

            const selectedSet = new Set(selectedStudentIds.value);

            return list.every((student) => selectedSet.has(student.id));
        },
        set(checked: boolean) {
            if (checked) {
                selectedStudentIds.value = selectableStudents.value.map((student) => student.id);
            } else {
                selectedStudentIds.value = [];
            }
        },
    });

    const selectedCount = computed(() => selectedStudentIds.value.length);

    const clearSelection = (): void => {
        selectedStudentIds.value = [];
    };

    const pruneSelectionToVisibleStudents = (): void => {
        const visibleSelectableIds = new Set(selectableStudents.value.map((student) => student.id));
        selectedStudentIds.value = selectedStudentIds.value.filter((id) => visibleSelectableIds.has(id));
    };

    return {
        selectedStudentIds,
        selectAllModel,
        selectedCount,
        clearSelection,
        pruneSelectionToVisibleStudents,
    };
}
